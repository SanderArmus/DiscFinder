<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        />
        <title>{{ __('Service temporarily unavailable') }}</title>
    </head>
    <body class="min-h-screen bg-gray-50 text-foreground dark:bg-[#0a0a0a] dark:text-white">
        <main class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-4 py-10">
            <div class="w-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-[#121212]">
                <h1 class="mb-2 text-center text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    {{ app()->getLocale() === 'et' ? 'Hoolduses' : 'Maintenance' }}
                </h1>

                <p class="mb-6 text-center text-sm text-gray-600 dark:text-gray-300">
                    {{ app()->getLocale() === 'et'
                        ? 'Me hooldame süsteemi. Palun tule hiljem tagasi.'
                        : 'We are currently maintaining the system. Please try again later.' }}
                </p>

                <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                    <p class="font-bold">
                        {{ app()->getLocale() === 'et' ? 'Tänan mõistmise eest.' : 'Thanks for your patience.' }}
                    </p>
                    <p class="mt-1">
                        {{ app()->getLocale() === 'et' ? 'Sõnumid ja vastete info võivad olla ajutiselt kättesaamatud.' : 'Messages and match info may be temporarily unavailable.' }}
                    </p>
                </div>
            </div>
        </main>
    </body>
</html>

