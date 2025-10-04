<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'N8NProxy')); ?> - <?php echo $__env->yieldContent('title', 'Dashboard'); ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="<?php echo e(route('dashboard')); ?>" class="text-xl font-bold text-gray-800">
                                N8NProxy
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <?php if(auth()->guard()->check()): ?>
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                                Dashboard
                            </a>
                            <a href="<?php echo e(route('webhooks')); ?>" class="nav-link <?php echo e(request()->routeIs('webhooks') ? 'active' : ''); ?>">
                                Webhooks
                            </a>
                            <a href="<?php echo e(route('actions-audit')); ?>" class="nav-link <?php echo e(request()->routeIs('actions-audit') ? 'active' : ''); ?>">
                                Actions Audit
                            </a>
                            <?php if(auth()->user()->is_admin ?? false): ?>
                            <a href="<?php echo e(route('admin.index')); ?>" class="nav-link <?php echo e(request()->routeIs('admin.*') ? 'active' : ''); ?>">
                                Admin
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right side -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <?php if(auth()->guard()->check()): ?>
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-700"><?php echo e(auth()->user()->email); ?></span>
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="flex space-x-4">
                            <a href="<?php echo e(route('login')); ?>" class="text-gray-500 hover:text-gray-700">Login</a>
                            <a href="<?php echo e(route('register')); ?>" class="text-gray-500 hover:text-gray-700">Register</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 mx-4 mt-4">
            <?php echo e(session('success')); ?>

        </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 mx-4 mt-4">
            <?php echo e(session('error')); ?>

        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <main>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <style>
        .nav-link {
            @apply inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out;
        }
        .nav-link.active {
            @apply border-indigo-400 text-gray-900;
        }
    </style>
</body>
</html><?php /**PATH /Users/abdelgadir/Documents/N8NProxy/app/build/stage/resources/views/layouts/app.blade.php ENDPATH**/ ?>