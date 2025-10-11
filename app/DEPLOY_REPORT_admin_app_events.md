# Deploy Report ? Admin App Events Logs

## curl -I (admin pages)
```
HTTP/1.1 302 Found
Server: nginx/1.26.3 (Ubuntu)
Content-Type: text/html; charset=utf-8
Connection: keep-alive
Cache-Control: no-cache, private
Date: Sat, 11 Oct 2025 10:25:24 GMT
Location: https://app.n8ndesigner.com/login
Set-Cookie: XSRF-TOKEN=eyJpdiI6Ill1UHdoV2tCSkh3YnZrN3NDcmpDcGc9PSIsInZhbHVlIjoiT3ZNV25tN09MMWU2QVVCY29hK3ljN1hIOTRrU3ZMZWZ4MlRQU3l4UmE2NFVNY0xYdVNrUGZDMnN1SHMyOWhBWUVJcXNrc3JLVnZ5aTB6cXpvK1I4bzgwSjI1ck1zRmNHb1VQVjNnN2pYSS9DUGFKc1BpVEwwZ2hKT3l6cnpkb2ciLCJtYWMiOiJhNDhiMThkYjc3OTQxOTVlZTM2YTg2MjYwOTMxMWQzOGRkODRlOTBmN2FkYjFlOThiYWI0NzQyMGRmZDg5M2NmIiwidGFnIjoiIn0%3D; expires=Sat, 11 Oct 2025 12:25:24 GMT; Max-Age=7200; path=/; domain=.n8ndesigner.com; secure; samesite=lax
Set-Cookie: n8nproxy_session=eyJpdiI6Im44bU8zMGEyN25pR1VsYmhyOHQrTFE9PSIsInZhbHVlIjoiSDRrT3IrZ0M1TVBVbUMvb2lDeElRMlRhM3pENWs4N0VQRjhJT3dHNzBzeERLQkFHMVBhZVYzSDgyaUtVdjVGZEZwTHRQWnJKTWQ0bTZQdldvUFZwWjlTUzZZcFBRcjhBWVV5ZnFoYzIzOFB6RlRWcUtKYis0a1ZKVkM5cjBtZ1EiLCJtYWMiOiJlOTI1MDM0MjAzYjQ2YzVjYjY5YmEyZjZlNjI5ZGEzNjUyN2UzMGE3YzU0MTlmNTYyM2YwODZmZTk5MzkwNGUyIiwidGFnIjoiIn0%3D; expires=Sat, 11 Oct 2025 12:25:24 GMT; Max-Age=7200; path=/; domain=.n8ndesigner.com; secure; httponly; samesite=lax


HTTP/1.1 302 Found
Server: nginx/1.26.3 (Ubuntu)
Content-Type: text/html; charset=utf-8
Connection: keep-alive
Cache-Control: no-cache, private
Date: Sat, 11 Oct 2025 10:25:24 GMT
Location: https://app.n8ndesigner.com/login
Set-Cookie: XSRF-TOKEN=eyJpdiI6IkYxc3B1VFVzK1ZZYlZNeDZtME41V2c9PSIsInZhbHVlIjoic2xYUU81UVVvTHREVHI0ckN1NnNOUHEyVDB4TGdOTnpDN3Zpd3hHdFUzSHhYcUpGYkg4djZWdWlEeHhxZjUzbGlsNkFXdUZZdEc3UnVxYWVPQVh4MUhMR0pDS0EzT2ZKMVlpOWQ5cE1Wa2tuNU4vL1U1bHJXSGF5bnlxU2ozTXEiLCJtYWMiOiJkY2Q2YTMwNWI4MTlmNDhlMjRiNzRlNjMzYmVhODdlMGI4NzkxOTgzNzJmZGFlMDIyNzg4ZDEzNDNmOTIwMDZiIiwidGFnIjoiIn0%3D; expires=Sat, 11 Oct 2025 12:25:24 GMT; Max-Age=7200; path=/; domain=.n8ndesigner.com; secure; samesite=lax
Set-Cookie: n8nproxy_session=eyJpdiI6ImJ0b3UyNlBuM0JMOUlDODJ4QmRNZXc9PSIsInZhbHVlIjoiYk94ZWJLMnY4UHcvMU5BcldLVVdpWWRvTWVHZGJZQVdLWmxyZUtVdzJUOHQ5d2ZudVdRZ0MrSm5Ed2JCekw0NTJVcmxzNVk4Qm9pMXJaNHRVcG1lbUc3dm02N1cwZFM3Q1puRnF5S3pKU1JvTEhhT253bkFHbnVEeDFsM2x3Y3UiLCJtYWMiOiIxNmMzOTA0ODZkMDEzZTYyNDY1YmZmNmM2MzFiMzBkZDkyNzdjMjlhM2FkNGQ3MjBjODJkMjUyOTQwZjE5MDdlIiwidGFnIjoiIn0%3D; expires=Sat, 11 Oct 2025 12:25:24 GMT; Max-Age=7200; path=/; domain=.n8ndesigner.com; secure; httponly; samesite=lax

```

## Recent log excerpt
```
#6 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack()
#7 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(750): Illuminate\\Routing\\Router->runRoute()
#8 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute()
#9 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(201): Illuminate\\Routing\\Router->dispatch()
#10 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(170): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():198}()
#11 /var/www/n8nproxy/app/vendor/livewire/livewire/src/Features/SupportDisablingBackButtonCache/DisableBackButtonCacheMiddleware.php(19): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():168}()
#12 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Livewire\\Features\\SupportDisablingBackButtonCache\\DisableBackButtonCacheMiddleware->handle()
#13 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#14 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#15 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#16 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#17 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#18 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#19 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#20 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#21 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(110): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#22 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#23 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(49): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#24 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\HandleCors->handle()
#25 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#26 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#27 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#28 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#29 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(127): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#30 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(176): Illuminate\\Pipeline\\Pipeline->then()
#31 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(145): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#32 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#33 /var/www/n8nproxy/app/public/index.php(17): Illuminate\\Foundation\\Application->handleRequest()
#34 {main}
"} 
[2025-10-09 17:26:24] production.ERROR: syntax error, unexpected token ")", expecting variable {"exception":"[object] (ParseError(code: 0): syntax error, unexpected token \")\", expecting variable at /var/www/n8nproxy/app/app/Http/Controllers/MerchantController.php:93)
[stacktrace]
#0 /var/www/n8nproxy/app/vendor/composer/ClassLoader.php(427): {closure:Composer\\Autoload\\ClassLoader::initializeIncludeClosure():575}()
#1 [internal function]: Composer\\Autoload\\ClassLoader->loadClass()
#2 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Route.php(1117): is_a()
#3 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Route.php(1054): Illuminate\\Routing\\Route->controllerMiddleware()
#4 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(820): Illuminate\\Routing\\Route->gatherMiddleware()
#5 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(802): Illuminate\\Routing\\Router->gatherRouteMiddleware()
#6 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(786): Illuminate\\Routing\\Router->runRouteWithinStack()
#7 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(750): Illuminate\\Routing\\Router->runRoute()
#8 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Routing/Router.php(739): Illuminate\\Routing\\Router->dispatchToRoute()
#9 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(201): Illuminate\\Routing\\Router->dispatch()
#10 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(170): Illuminate\\Foundation\\Http\\Kernel->{closure:Illuminate\\Foundation\\Http\\Kernel::dispatchToRouter():198}()
#11 /var/www/n8nproxy/app/vendor/livewire/livewire/src/Features/SupportDisablingBackButtonCache/DisableBackButtonCacheMiddleware.php(19): Illuminate\\Pipeline\\Pipeline->{closure:Illuminate\\Pipeline\\Pipeline::prepareDestination():168}()
#12 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Livewire\\Features\\SupportDisablingBackButtonCache\\DisableBackButtonCacheMiddleware->handle()
#13 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#14 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/ConvertEmptyStringsToNull.php(31): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#15 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\ConvertEmptyStringsToNull->handle()
#16 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TransformsRequest.php(21): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#17 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/TrimStrings.php(51): Illuminate\\Foundation\\Http\\Middleware\\TransformsRequest->handle()
#18 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\TrimStrings->handle()
#19 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/ValidatePostSize.php(27): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#20 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\ValidatePostSize->handle()
#21 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/PreventRequestsDuringMaintenance.php(110): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#22 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\PreventRequestsDuringMaintenance->handle()
#23 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/HandleCors.php(49): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#24 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\HandleCors->handle()
#25 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Http/Middleware/TrustProxies.php(58): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#26 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Http\\Middleware\\TrustProxies->handle()
#27 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/InvokeDeferredCallbacks.php(22): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#28 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(209): Illuminate\\Foundation\\Http\\Middleware\\InvokeDeferredCallbacks->handle()
#29 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(127): Illuminate\\Pipeline\\Pipeline->{closure:{closure:Illuminate\\Pipeline\\Pipeline::carry():184}:185}()
#30 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(176): Illuminate\\Pipeline\\Pipeline->then()
#31 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(145): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()
#32 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1220): Illuminate\\Foundation\\Http\\Kernel->handle()
#33 /var/www/n8nproxy/app/public/index.php(17): Illuminate\\Foundation\\Application->handleRequest()
#34 {main}
"} 
[2025-10-09 19:10:09] production.ERROR: Command "test" is not defined.

Did you mean one of these?
    make:test
    schedule:test {"exception":"[object] (Symfony\\Component\\Console\\Exception\\CommandNotFoundException(code: 0): Command \"test\" is not defined.

Did you mean one of these?
    make:test
    schedule:test at /var/www/n8nproxy/app/vendor/symfony/console/Application.php:743)
[stacktrace]
#0 /var/www/n8nproxy/app/vendor/symfony/console/Application.php(301): Symfony\\Component\\Console\\Application->find()
#1 /var/www/n8nproxy/app/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun()
#2 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(198): Symfony\\Component\\Console\\Application->run()
#3 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle()
#4 /var/www/n8nproxy/app/artisan(13): Illuminate\\Foundation\\Application->handleCommand()
#5 {main}
"} 
[2025-10-09 20:02:43] production.ERROR: PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR on line 1 {"exception":"[object] (Psy\\Exception\\ParseErrorException(code: 0): PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR on line 1 at /var/www/n8nproxy/app/vendor/psy/psysh/src/Exception/ParseErrorException.php:44)
[stacktrace]
#0 /var/www/n8nproxy/app/vendor/psy/psysh/src/CodeCleaner.php(306): Psy\\Exception\\ParseErrorException::fromParseError()
#1 /var/www/n8nproxy/app/vendor/psy/psysh/src/CodeCleaner.php(240): Psy\\CodeCleaner->parse()
#2 /var/www/n8nproxy/app/vendor/psy/psysh/src/Shell.php(852): Psy\\CodeCleaner->clean()
#3 /var/www/n8nproxy/app/vendor/psy/psysh/src/Shell.php(881): Psy\\Shell->addCode()
#4 /var/www/n8nproxy/app/vendor/psy/psysh/src/Shell.php(1394): Psy\\Shell->setCode()
#5 /var/www/n8nproxy/app/vendor/laravel/tinker/src/Console/TinkerCommand.php(76): Psy\\Shell->execute()
#6 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Laravel\\Tinker\\Console\\TinkerCommand->handle()
#7 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\\Container\\BoundMethod::{closure:Illuminate\\Container\\BoundMethod::call():35}()
#8 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(95): Illuminate\\Container\\Util::unwrapIfClosure()
#9 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod()
#10 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Container/Container.php(696): Illuminate\\Container\\BoundMethod::call()
#11 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Console/Command.php(213): Illuminate\\Container\\Container->call()
#12 /var/www/n8nproxy/app/vendor/symfony/console/Command/Command.php(318): Illuminate\\Console\\Command->execute()
#13 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Console/Command.php(182): Symfony\\Component\\Console\\Command\\Command->run()
#14 /var/www/n8nproxy/app/vendor/symfony/console/Application.php(1110): Illuminate\\Console\\Command->run()
#15 /var/www/n8nproxy/app/vendor/symfony/console/Application.php(359): Symfony\\Component\\Console\\Application->doRunCommand()
#16 /var/www/n8nproxy/app/vendor/symfony/console/Application.php(194): Symfony\\Component\\Console\\Application->doRun()
#17 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(198): Symfony\\Component\\Console\\Application->run()
#18 /var/www/n8nproxy/app/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle()
#19 /var/www/n8nproxy/app/artisan(13): Illuminate\\Foundation\\Application->handleCommand()
#20 {main}
"} 
[2025-10-09 20:05:56] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:05:56] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:06:08] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:06:09] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:07:48] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:07:51] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:12:35] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:12:47] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:13:33] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:13:36] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:13:46] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:13:46] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:14:28] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:15:38] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:15:38] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:21:39] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:21:51] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:23:31] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:24:29] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:24:32] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:31:08] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:32:20] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:32:21] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 20:40:12] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:11:12] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:11:12] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:17:51] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:19:01] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:19:02] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-09 23:26:59] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:06:22] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:06:26] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:06:32] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:06:39] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:08:14] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:08:21] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:24:58] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 14:25:01] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 17:11:38] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-10 17:11:42] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
[2025-10-11 12:06:43] production.WARNING: Salla webhook rejected due to invalid token {"path":"webhooks/salla/app-events","mode":"token","has_header":false,"has_query":false} 
```

UTC 2025-10-11 10:25:24
