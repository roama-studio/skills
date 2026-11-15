<?php
/**
 * Order bot config. Copy to order-bot-config.php in the hosting's home folder, OUTSIDE the web
 * root (next to public_html, not inside it). Never commit it, never send it in a letter.
 */
return array(
	// From BotFather on the CLIENT's Telegram account.
	'token'        => 'PASTE_THE_BOT_TOKEN_HERE',
	// Any random string of 16-256 chars (A-Z a-z 0-9 _ -); the same one goes to setWebhook.
	'secret'       => 'PASTE_A_RANDOM_SECRET_HERE',
	// Who gets the orders: chat ids of the owner or the admins.
	'owner_chats'  => array( '123456789' ),
	// An Apps Script web app that writes a row per order (optional); empty = no sheet.
	'sheet_url'    => '',
	'sheet_secret' => '',
	'business'     => 'Кав\'ярня «Демо»',
	'site'         => 'https://example.com/',
	// Where the guests' conversation states are kept (outside the web root).
	'state_dir'    => __DIR__ . '/order-bot-state',
	'menu_file'    => __DIR__ . '/order-bot-menu.json',
	'texts'        => array(
		'hello'    => 'Вітаємо! Що бажаєте?',
		'name'     => 'Як до вас звертатися?',
		'phone'    => 'Ваш номер телефону? Він потрібен лише, щоб підтвердити замовлення.',
		'bad'      => 'Схоже, номер неповний. Напишіть, будь ласка, ще раз.',
		'thanks'   => 'Дякуємо, замовлення прийнято! Ми скоро зв\'яжемося з вами.',
		'hours'    => 'Працюємо щодня 08:00-21:00.',
	),
);
