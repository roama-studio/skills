<?php
/**
 * Order / booking bot webhook. One file, no WordPress, no database.
 * Telegram -> this file (setWebhook with secret_token) -> the conversation on inline keyboards ->
 * the order to the owner's Telegram and, optionally, a row of the client's Google Sheet.
 *
 * The config lives OUTSIDE the web root. For public_html/bot/webhook.php the default is the hosting's home
 * folder: dirname(__DIR__, 2) . '/order-bot-config.php'; a hosting with another layout sets ORDER_BOT_CONFIG.
 * The token is never logged: every error text goes through obot_redact().
 */

$cfg_file = getenv( 'ORDER_BOT_CONFIG' ) ?: dirname( __DIR__, 2 ) . '/order-bot-config.php';
if ( ! is_file( $cfg_file ) ) {
	http_response_code( 500 );
	exit( 'no config' );
}
$cfg = require $cfg_file;

// --- only Telegram, with our secret --------------------------------------------------------------
$given = isset( $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ) ? (string) $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
if ( '' === $cfg['secret'] || ! hash_equals( (string) $cfg['secret'], $given ) ) {
	// The header Telegram sends is X-Telegram-Bot-Api-Secret-Token.
	http_response_code( 403 );
	exit;
}
$update = json_decode( (string) file_get_contents( 'php://input' ), true );
if ( ! is_array( $update ) ) {
	exit( 'ok' );
}
$menu = json_decode( (string) file_get_contents( $cfg['menu_file'] ), true );

function obot_redact( $text ) {
	global $cfg;
	return str_replace( (string) $cfg['token'], '[token]', (string) $text );
}

function obot_log( $message ) {
	error_log( '[order-bot] ' . obot_redact( $message ) );
}

function obot_api( $method, array $params ) {
	global $cfg;
	$ch = curl_init( 'https://api.telegram.org/bot' . $cfg['token'] . '/' . $method );
	curl_setopt_array( $ch, array(
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode( $params ),
		CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 5,
	) );
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	curl_close( $ch );
	$data = json_decode( (string) $body, true );
	if ( 200 !== $code || empty( $data['ok'] ) ) {
		obot_log( sprintf( '%s: HTTP %d %s %s', $method, $code, $err, isset( $data['description'] ) ? $data['description'] : '' ) );
		return false;
	}
	return $data['result'];
}

function obot_esc( $text ) {
	return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), (string) $text );
}

// --- the guest's state: one small JSON file per chat, 2 hours -----------------------------------
function obot_state_file( $chat ) {
	global $cfg;
	if ( ! is_dir( $cfg['state_dir'] ) ) {
		mkdir( $cfg['state_dir'], 0700, true );
	}
	return $cfg['state_dir'] . '/' . preg_replace( '/[^0-9-]/', '', (string) $chat ) . '.json';
}

function obot_state( $chat ) {
	$file = obot_state_file( $chat );
	$s    = is_file( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
	if ( ! is_array( $s ) || time() - (int) $s['at'] > 7200 ) {
		$s = array( 'step' => 'start', 'basket' => array(), 'name' => '', 'phone' => '', 'time' => '', 'at' => time() );
	}
	return $s;
}

function obot_save( $chat, array $s ) {
	$s['at'] = time();
	file_put_contents( obot_state_file( $chat ), json_encode( $s ), LOCK_EX );
}

function obot_item( $id ) {
	global $menu;
	foreach ( $menu['categories'] as $cat ) {
		foreach ( $cat['items'] as $item ) {
			if ( $item['id'] === $id ) {
				return $item;
			}
		}
	}
	return null;
}

function obot_basket_text( array $s ) {
	global $menu;
	$lines = array();
	$total = 0;
	foreach ( $s['basket'] as $id => $n ) {
		$item = obot_item( $id );
		if ( $item ) {
			$lines[] = sprintf( '%s × %d', $item['title'], $n );
			$total  += $item['price'] * $n;
		}
	}
	if ( '' !== $s['time'] ) {
		$lines[] = 'Бронь на ' . $s['time'];
	}
	if ( $total ) {
		$lines[] = sprintf( 'Разом: %d %s', $total, $menu['currency'] );
	}
	return $lines ? implode( "\n", $lines ) : 'Поки порожньо';
}

// --- screens ---------------------------------------------------------------------------------
function obot_home( $chat ) {
	global $cfg, $menu;
	$rows = array();
	foreach ( $menu['categories'] as $cat ) {
		$rows[] = array( array( 'text' => $cat['title'], 'callback_data' => 'c:' . $cat['id'] ) );
	}
	if ( ! empty( $menu['booking']['enabled'] ) ) {
		$rows[] = array( array( 'text' => 'Забронювати столик', 'callback_data' => 'book' ) );
	}
	obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $cfg['texts']['hello'], 'reply_markup' => array( 'inline_keyboard' => $rows ) ) );
}

function obot_category( $chat, $cat_id, array $s ) {
	global $menu;
	foreach ( $menu['categories'] as $cat ) {
		if ( $cat['id'] !== $cat_id ) {
			continue;
		}
		$rows = array();
		foreach ( $cat['items'] as $item ) {
			$rows[] = array( array( 'text' => sprintf( '+ %s · %d %s', $item['title'], $item['price'], $menu['currency'] ), 'callback_data' => 'a:' . $item['id'] ) );
		}
		$rows[] = array( array( 'text' => '← Меню', 'callback_data' => 'home' ), array( 'text' => 'Далі →', 'callback_data' => 'next' ) );
		obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => obot_basket_text( $s ), 'reply_markup' => array( 'inline_keyboard' => $rows ) ) );
	}
}

function obot_summary( $chat, array $s ) {
	$text = "Перевірте, будь ласка:\n" . obot_basket_text( $s ) . "\nІм'я: " . $s['name'] . "\nТелефон: " . $s['phone'];
	obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $text, 'reply_markup' => array( 'inline_keyboard' => array( array(
		array( 'text' => 'Підтвердити', 'callback_data' => 'ok' ),
		array( 'text' => 'Змінити', 'callback_data' => 'home' ),
	) ) ) ) );
}

// --- the order: owner chats + the sheet --------------------------------------
function obot_send_sheet( array $order ) {
	global $cfg;
	if ( '' === $cfg['sheet_url'] ) {
		return;
	}
	$payload = json_encode( array(
		'secret' => $cfg['sheet_secret'],
		'id'     => $order['id'],
		'time'   => $order['time'],
		'site'   => $cfg['site'],
		'form'   => 'Telegram-бот',
		'page'   => '',
		'fields' => $order['fields'],
		'source' => (object) array( 'channel' => 'telegram' ),
	) );
	// Apps Script answers the POST with a 302 to the result: do not follow it as a POST, GET the Location.
	$ch = curl_init( $cfg['sheet_url'] );
	curl_setopt_array( $ch, array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => array( 'Content-Type: application/json' ),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 8 ) );
	$raw  = (string) curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	if ( in_array( $code, array( 301, 302, 303, 307, 308 ), true ) && preg_match( '/^location:\s*(https:\/\/\S+)/mi', $raw, $m ) ) {
		$ch = curl_init( trim( $m[1] ) );
		curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 2, CURLOPT_TIMEOUT => 8 ) );
		$raw  = (string) curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
	}
	$data = json_decode( substr( $raw, strpos( $raw, '{' ) ?: 0 ), true );
	if ( 200 !== $code || empty( $data['ok'] ) ) {
		obot_log( sprintf( 'sheet: HTTP %d', $code ) );
	}
}

function obot_order( $chat, array $s, array $from ) {
	global $cfg;
	$order = array(
		'id'     => 'tg-' . $chat . '-' . time(),
		'time'   => date( 'Y-m-d H:i' ),
		'fields' => array(
			array( 'label' => 'Замовлення', 'value' => obot_basket_text( $s ) ),
			array( 'label' => "Ім'я", 'value' => $s['name'] ),
			array( 'label' => 'Телефон', 'value' => $s['phone'] ),
			array( 'label' => 'Telegram', 'value' => isset( $from['username'] ) ? '@' . $from['username'] : (string) $chat ),
		),
	);
	$lines = array( '<b>' . obot_esc( 'Нове замовлення: ' . $cfg['business'] ) . '</b>' );
	foreach ( $order['fields'] as $f ) {
		$lines[] = '<b>' . obot_esc( $f['label'] ) . ':</b> ' . obot_esc( $f['value'] );
	}
	$lines[] = '<i>' . obot_esc( $order['time'] . ' · ' . $order['id'] ) . '</i>';
	foreach ( $cfg['owner_chats'] as $owner ) {
		obot_api( 'sendMessage', array( 'chat_id' => $owner, 'text' => mb_substr( implode( "\n", $lines ), 0, 4096 ), 'parse_mode' => 'HTML' ) );
	}
	obot_send_sheet( $order );
	// The guest hears «прийнято» even when an owner chat failed: the error is in the log.
	obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $cfg['texts']['thanks'] . "\n" . $cfg['texts']['hours'], 'reply_markup' => array( 'remove_keyboard' => true ) ) );
}

// --- the flow --------------------------------------------------------------------------------
if ( isset( $update['callback_query'] ) ) {
	$q    = $update['callback_query'];
	$chat = $q['message']['chat']['id'];
	$data = (string) $q['data'];
	$s    = obot_state( $chat );
	obot_api( 'answerCallbackQuery', array( 'callback_query_id' => $q['id'] ) );
	if ( 'home' === $data ) {
		$s['step'] = 'start';
		obot_home( $chat );
	} elseif ( 0 === strpos( $data, 'c:' ) ) {
		obot_category( $chat, substr( $data, 2 ), $s );
	} elseif ( 0 === strpos( $data, 'a:' ) && obot_item( substr( $data, 2 ) ) ) {
		$id                 = substr( $data, 2 );
		$s['basket'][ $id ] = ( isset( $s['basket'][ $id ] ) ? $s['basket'][ $id ] : 0 ) + 1;
		obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => obot_basket_text( $s ) ) );
	} elseif ( 'book' === $data ) {
		$rows = array_map( function ( $t ) { return array( array( 'text' => $t, 'callback_data' => 't:' . $t ) ); }, $menu['booking']['times'] );
		obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => 'На котру годину?', 'reply_markup' => array( 'inline_keyboard' => $rows ) ) );
	} elseif ( 0 === strpos( $data, 't:' ) && in_array( substr( $data, 2 ), $menu['booking']['times'], true ) ) {
		$s['time'] = substr( $data, 2 );
		$data      = 'next';
	}
	if ( 'next' === $data ) {
		$s['step'] = 'name';
		obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $cfg['texts']['name'] ) );
	} elseif ( 'ok' === $data && 'confirm' === $s['step'] ) {
		obot_order( $chat, $s, $q['from'] );
		$s = array( 'step' => 'start', 'basket' => array(), 'name' => '', 'phone' => '', 'time' => '' );
	}
	obot_save( $chat, $s );
} elseif ( isset( $update['message'] ) ) {
	$m    = $update['message'];
	$chat = $m['chat']['id'];
	$text = isset( $m['text'] ) ? trim( (string) $m['text'] ) : '';
	$s    = obot_state( $chat );
	if ( 0 === strpos( $text, '/start' ) ) {
		$s['step'] = 'start';
		obot_home( $chat );
	} elseif ( 'name' === $s['step'] && '' !== $text ) {
		$s['name'] = mb_substr( $text, 0, 80 );
		$s['step'] = 'phone';
		obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $cfg['texts']['phone'], 'reply_markup' => array(
			'keyboard' => array( array( array( 'text' => 'Поділитися номером', 'request_contact' => true ) ) ), 'one_time_keyboard' => true, 'resize_keyboard' => true ) ) );
	} elseif ( 'phone' === $s['step'] ) {
		$phone  = isset( $m['contact']['phone_number'] ) ? (string) $m['contact']['phone_number'] : $text;
		$digits = preg_replace( '/\D/', '', $phone );
		if ( strlen( $digits ) < 9 || strlen( $digits ) > 15 ) {
			obot_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $cfg['texts']['bad'] ) );
		} else {
			$s['phone'] = '+' . ltrim( $digits, '+' );
			$s['step']  = 'confirm';
			obot_summary( $chat, $s );
		}
	} else {
		obot_home( $chat );
	}
	obot_save( $chat, $s );
}
echo 'ok';
