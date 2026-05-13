<?php $__env->startSection('title', 'Convert PDF - '.config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Convert PDF</h1>
    <p class="text-gray-600 mt-1">
        Export a PDF to text, HTML, DOCX or an image. Image and high-fidelity DOCX
        conversions need additional tools (Imagick or LibreOffice) installed on the server.
    </p>
</div>

<?php if($documents->isEmpty()): ?>
    <div class="bg-white rounded-xl shadow p-12 text-center">
        <p class="text-gray-500">
            You don't have any PDFs yet.
            <a href="<?php echo e(route('pdf.index')); ?>" class="text-blue-600 hover:underline">Upload one first</a>.
        </p>
    </div>
<?php else: ?>
    <form method="POST" action="<?php echo e(route('pdf.convert.store')); ?>"
          x-data="{ target: '' }"
          class="bg-white rounded-xl shadow p-6 space-y-5">
        <?php echo csrf_field(); ?>

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
            <label class="block text-sm font-medium text-gray-700 mb-2">Target format</label>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $format): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex flex-col items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <span class="font-semibold text-gray-800 uppercase"><?php echo e($format); ?></span>
                        <span class="text-xs text-gray-500 mt-1">
                            <?php switch($format):
                                case ('txt'): ?> Plain text <?php break; ?>
                                <?php case ('html'): ?> Web page <?php break; ?>
                                <?php case ('docx'): ?> Word doc <?php break; ?>
                                <?php case ('jpg'): ?> JPEG image <?php break; ?>
                                <?php case ('png'): ?> PNG image <?php break; ?>
                            <?php endswitch; ?>
                        </span>
                        <input type="radio" name="target" value="<?php echo e($format); ?>" x-model="target" class="sr-only" required>
                    </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <p class="text-xs text-gray-500 mt-2"
               x-show="target === 'jpg' || target === 'png'"
               x-cloak>
                Image conversion uses ImageMagick (Imagick PHP extension) - make sure it's installed and PDF policy is enabled.
            </p>
            <p class="text-xs text-gray-500 mt-2"
               x-show="target === 'docx'"
               x-cloak>
                DOCX conversion uses LibreOffice if available; otherwise falls back to a plain-text DOCX.
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Convert &amp; download</button>
        </div>
    </form>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/pdf/convert.blade.php ENDPATH**/ ?>