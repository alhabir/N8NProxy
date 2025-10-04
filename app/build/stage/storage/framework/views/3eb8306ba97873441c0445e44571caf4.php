<div
    <?php echo e($attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)); ?>

>
    <?php echo e($getChildComponentContainer()); ?>

</div>
<?php /**PATH /Users/abdelgadir/Documents/N8NProxy/app/build/stage/vendor/filament/infolists/resources/views/components/group.blade.php ENDPATH**/ ?>