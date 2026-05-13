<?php $__env->startSection('title', 'Sign in - '.config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="flex min-h-screen items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-2xl border border-teal-900/10 bg-stone-50/95 p-8 shadow-2xl shadow-teal-950/20 backdrop-blur-md">
        <div class="mb-8 flex flex-col items-center text-center">
            <div class="mb-4 rounded-2xl bg-teal-950/5 p-2 ring-1 ring-teal-900/10" aria-hidden="true">
                <?php echo $__env->make('partials.brand-mark', ['class' => 'h-16 w-16'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <h1 class="font-display text-3xl font-bold tracking-tight text-teal-950"><?php echo e(config('app.name')); ?></h1>
            <p class="mt-1 text-sm text-teal-800/70">Sign in to open your library of PDFs</p>
        </div>

        <?php if($errors->any()): ?>
            <div class="mb-4 rounded-xl border border-red-200/90 bg-red-50/95 px-4 py-3 text-red-900">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <p><?php echo e($error); ?></p>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
            <div class="mb-4 rounded-xl border border-teal-200/80 bg-teal-50/95 px-4 py-3 text-teal-950">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('authenticate')); ?>" class="space-y-5">
            <?php echo csrf_field(); ?>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-teal-950">Email</label>
                <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                    class="w-full rounded-lg border border-teal-900/15 bg-white px-3 py-2 text-stone-900 shadow-inner shadow-teal-950/5 focus:outline-none focus:ring-2 focus:ring-amber-500/80">
            </div>
            <div>
                <div class="mb-1 flex items-center justify-between gap-2">
                    <label for="password" class="block text-sm font-medium text-teal-950">Password</label>
                    <a href="<?php echo e(route('password.request')); ?>" class="text-sm font-semibold text-amber-700 hover:text-amber-600">Forgot password?</a>
                </div>
                <input type="password" id="password" name="password" required
                    class="w-full rounded-lg border border-teal-900/15 bg-white px-3 py-2 text-stone-900 shadow-inner shadow-teal-950/5 focus:outline-none focus:ring-2 focus:ring-amber-500/80">
            </div>
            <label class="flex items-center text-sm text-teal-900/75">
                <input type="checkbox" name="remember" class="mr-2 rounded border-teal-800/30 text-amber-600 focus:ring-amber-500">
                Remember me
            </label>
            <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-teal-800 to-teal-950 py-2.5 font-semibold text-amber-50 shadow-lg shadow-teal-950/30 transition hover:from-teal-700 hover:to-teal-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">
                Sign in
            </button>
            <p class="text-center text-sm text-teal-900/70">
                Don't have an account?
                <a href="<?php echo e(route('register')); ?>" class="font-semibold text-amber-700 hover:text-amber-600">Register</a>
            </p>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/auth/login.blade.php ENDPATH**/ ?>