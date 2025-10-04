<input
    <?php echo e($attributes
            ->merge([
                'id' => $getId(),
                'type' => 'hidden',
                $applyStateBindingModifiers('wire:model') => $getStatePath(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class(['fi-fo-hidden'])); ?>

/>
<?php /**PATH /Users/abdelgadir/Documents/N8NProxy/app/build/stage/vendor/filament/forms/resources/views/components/hidden.blade.php ENDPATH**/ ?>