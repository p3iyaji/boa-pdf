<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Source+Sans+3:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Source Sans 3"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Cinzel', 'Georgia', 'ui-serif', 'serif'],
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-amber-50 via-stone-100 to-teal-100/90 font-sans text-stone-900 antialiased">
    <?php if(auth()->guard()->check()): ?>
        <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <main class="min-h-screen min-w-0 max-w-full overflow-x-hidden px-4 pb-8 max-lg:pt-[calc(env(safe-area-inset-top,0px)+3.5rem+1rem)] lg:ml-64 lg:px-8 lg:pb-10 lg:pt-8 md:px-6">
            <?php if(session('success')): ?>
                <div class="mb-4 rounded-xl border border-teal-200/80 bg-teal-50/95 px-4 py-3 text-teal-950 shadow-sm shadow-teal-900/5 backdrop-blur-sm">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="mb-4 rounded-xl border border-red-200/90 bg-red-50/95 px-4 py-3 text-red-900 shadow-sm backdrop-blur-sm">
                    <ul class="list-inside list-disc">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>
    <?php else: ?>
        <?php echo $__env->yieldContent('content'); ?>
    <?php endif; ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/layouts/app.blade.php ENDPATH**/ ?>