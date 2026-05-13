<?php $__env->startSection('title', 'Compress PDF - '.config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Compress PDF</h1>
    <p class="text-gray-600 mt-1">Pick a level. With Ghostscript, we re-encode embedded images (not just pass-through JPEGs) so photo and scan PDFs usually shrink a lot, similar to online tools. Optional <span class="font-semibold text-gray-800">qpdf</span> (11+) can optimize further. Text-only or already-optimized files may not get much smaller. Without Ghostscript, the fallback only repacks pages and rarely reduces size.</p>
</div>

<?php if($documents->isEmpty()): ?>
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <p class="text-gray-500">No PDFs to compress yet. <a href="<?php echo e(route('pdf.index')); ?>" class="text-blue-600 hover:underline">Upload one</a>.</p>
    </div>
<?php else: ?>
    <form method="POST" action="<?php echo e(route('pdf.compress.store')); ?>" class="bg-white rounded-xl shadow p-6 space-y-5">
        <?php echo csrf_field(); ?>

        <?php $__errorArgs = ['compress'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm" role="alert"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <div>
            <label for="document_id" class="block text-sm font-medium text-gray-700 mb-1">Document</label>
            <select id="document_id" name="document_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Choose a PDF&hellip;</option>
                <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($doc->id); ?>"><?php echo e($doc->original_name); ?> (<?php echo e($doc->human_file_size); ?>)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Compression level</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php $__currentLoopData = $levels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex flex-col p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <span class="font-semibold text-gray-800 capitalize"><?php echo e($level); ?></span>
                        <span class="text-xs text-gray-500 mt-1">
                            <?php switch($level):
                                case ('low'): ?> Highest quality (print), little size change <?php break; ?>
                                <?php case ('medium'): ?> Stronger shrink, still print-usable <?php break; ?>
                                <?php case ('recommended'): ?> Smaller images (~96&nbsp;dpi), good default <?php break; ?>
                                <?php case ('maximum'): ?> Smallest (~72&nbsp;dpi images), try for big scans <?php break; ?>
                            <?php endswitch; ?>
                        </span>
                        <input type="radio" name="level" value="<?php echo e($level); ?>"
                               class="sr-only" <?php echo e($level === $default ? 'checked' : ''); ?>>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Compress</button>
        </div>
    </form>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/pdf/compress.blade.php ENDPATH**/ ?>