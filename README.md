<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Admin content and incoming messages

The application uses MySQL/MariaDB at `127.0.0.1:3306` with a database named `dev_plus`. Configure `DB_USERNAME` and `DB_PASSWORD` in `.env` to match the local MySQL account, create the database if needed, then run `php artisan migrate`.

The admin panel at `/admin` includes management menus for articles, categories, orders, and messages.

Incoming integrations can send a `POST` request to `/api/messages/incoming` with an `X-Integration-Token` header and a JSON body containing `body`. Optional fields are `sender_name`, `sender_email`, `subject`, `source`, `external_id`, and `conversation_id`. Send the same stable `conversation_id` for every message in a chat so the first message gets the welcome reply and later messages include the conversation history. If no conversation ID is available, `sender_email` is used as a fallback when present. The endpoint stores the message and returns the automatic reply in the `reply` field; the integration must forward it to its original messaging channel.

The welcome message uses `GEMINI_BUSINESS_NAME` (currently `Momo Shop's`). Gemini receives the published articles from the Articles menu as its catalog. Include variant names, prices, and any useful details in each article's content; Gemini will use only those published details and must not invent missing prices or variants. If no articles have been published, it asks the customer to clarify their need.

Set `GEMINI_API_KEY` and `MESSAGE_INTEGRATION_TOKEN` in the server environment before enabling the integration. `GEMINI_MODEL`, `GEMINI_BUSINESS_NAME`, and `GEMINI_SYSTEM_INSTRUCTION` can be used to configure the model and reply guidance. Messages are retained in the admin inbox if Gemini is unavailable, and can be retried from the Messages menu.

## WhatsApp Business

The assistant can receive WhatsApp text messages through Meta's WhatsApp Cloud API and send its replies back to the same customer. Configure `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_VERIFY_TOKEN`, and `WHATSAPP_APP_SECRET` in the server environment. `WHATSAPP_VERIFY_TOKEN` is a private value you choose; it must match the value entered when configuring the webhook in Meta. The Graph API version defaults to `v26.0` and can be changed with `WHATSAPP_GRAPH_API_VERSION`.

In the Meta developer dashboard, configure the webhook callback URL as `https://your-domain/api/webhooks/whatsapp`, use the same verify token, and subscribe the WhatsApp product to the `messages` field. The server must be publicly reachable over HTTPS. Incoming text and interactive button/list replies are handled; other message types are currently ignored. Run `php artisan migrate` before enabling the webhook so WhatsApp delivery can be tracked and duplicate webhook deliveries do not normally resend an already acknowledged reply.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
