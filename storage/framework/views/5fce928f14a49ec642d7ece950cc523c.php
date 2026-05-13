<?php $__env->startSection('title', 'My PDFs - '.config('app.name')); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 sm:text-3xl">My PDFs</h1>
        <p class="mt-1 text-sm text-gray-600 sm:text-base">Upload, view, and manage your PDF library.</p>
    </div>
</div>

<div class="mb-6 rounded-xl bg-white p-4 shadow sm:p-6">
    <form method="POST" action="<?php echo e(route('pdf.upload')); ?>" enctype="multipart/form-data"
          x-data="{ fileName: null, dragging: false }"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files; fileName = $refs.file.files[0]?.name; $refs.form.requestSubmit();"
          x-ref="form">
        <?php echo csrf_field(); ?>
        <label class="block cursor-pointer rounded-xl border-2 border-dashed p-8 text-center transition sm:p-10"
               :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50'">
            <input type="file" name="file" accept="application/pdf" class="hidden" x-ref="file"
                   @change="fileName = $event.target.files[0]?.name; $refs.form.requestSubmit();">
            <div class="flex flex-col items-center space-y-2">
                <svg class="h-10 w-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                <p class="font-medium text-gray-700">Drop a PDF here, or click to select</p>
                <p class="text-xs text-gray-500">Max <?php echo e(config('pdf.max_file_size') / 1024); ?> MB</p>
                <p x-show="fileName" class="max-w-xs truncate text-sm text-blue-600" x-text="fileName"></p>
            </div>
        </label>
    </form>
</div>

<?php if($documents->isEmpty()): ?>
    <div class="rounded-xl bg-white p-12 text-center shadow">
        <p class="text-gray-500">You haven't uploaded any PDFs yet.</p>
    </div>
<?php else: ?>
    
    <div class="space-y-3 lg:hidden">
        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <a href="<?php echo e(route('pdf.show', $doc)); ?>" class="block font-semibold text-blue-600 hover:text-blue-800"><?php echo e($doc->original_name); ?></a>
                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                    <?php echo e(ucfirst($doc->operation_type)); ?>

                    <span class="text-gray-300">&middot;</span> <?php echo e($doc->pages); ?> pages
                    <span class="text-gray-300">&middot;</span> <?php echo e($doc->human_file_size); ?>

                    <span class="text-gray-300">&middot;</span> <?php echo e($doc->created_at->diffForHumans()); ?>

                </p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <?php echo $__env->make('pdf._icon-actions', ['doc' => $doc, 'large' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <a href="<?php echo e(route('pdf.sign.create', $doc)); ?>" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg bg-emerald-600 px-4 text-center text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 active:bg-emerald-800 sm:flex-initial">Sign</a>
                </div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow lg:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">Name</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">Type</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">Pages</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">Size</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">Uploaded</th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 lg:px-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-gray-50/80">
                            <td class="max-w-[14rem] px-4 py-3 lg:max-w-xs lg:px-6 lg:py-4">
                                <a href="<?php echo e(route('pdf.show', $doc)); ?>" class="font-medium text-blue-600 hover:text-blue-800"><?php echo e($doc->original_name); ?></a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 lg:px-6 lg:py-4"><?php echo e(ucfirst($doc->operation_type)); ?></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 lg:px-6 lg:py-4"><?php echo e($doc->pages); ?></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 lg:px-6 lg:py-4"><?php echo e($doc->human_file_size); ?></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 lg:px-6 lg:py-4"><?php echo e($doc->created_at->diffForHumans()); ?></td>
                            <td class="px-4 py-3 lg:px-6 lg:py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <?php echo $__env->make('pdf._icon-actions', ['doc' => $doc, 'large' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <a href="<?php echo e(route('pdf.sign.create', $doc)); ?>" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 sm:text-sm">Sign</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4"><?php echo e($documents->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/pauliyaji/Herd/powerhouse/resources/views/pdf/index.blade.php ENDPATH**/ ?>