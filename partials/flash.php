<?php
/*put this at the bottom of the page so any templates
 populate the flash variable and then display at the proper timing*/
?>
<div class="container" id="flash">
    <?php $messages = getMessages(); ?>
    <?php if ($messages) : ?>
        <?php foreach ($messages as $msg) : ?>
            <!-- bootstrap classes will be utilized when we add bootstrap in a future lesson-->
            <div class="row justify-content-center">
                <!-- color matches bootstrap color classes-->
                <div class="alert alert-<?php se($msg, 'color', 'info'); ?>" role="alert">
                    <?php se($msg, "text", ""); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>

    #flash{
      position: fixed;
      top: 1px;         
      left: 50%;
      transform: translateX(-50%);
      width: 90%;
      max-width: 700px;
      z-index: 99999;     
      opacity: 1;
      background: transparent;
    }

    #flash .alert {
        padding: 14px 16px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        border: 1px solid transparent;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    #flash:empty {
        display: none;
    }
</style>