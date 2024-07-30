<?php
# MagiaPHP 
# file date creation: 2024-03-14 
?>

<?php include view("home", "header"); ?>                

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">      
        <?php include view("user_options", "izq_edit"); ?>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-8 col-lg-8">
        <h1>
            <?php _menu_icon("top", 'user_options'); ?>
            <?php _t("User_options edit"); ?>
        </h1>
        <hr>
        <?php
        if ($_REQUEST) {
            foreach ($error as $key => $value) {
                message("info", "$value");
            }
        }
        ?>            
        <?php include view("user_options", "form_edit", $arg = ["redi" => 1]); ?>
    </div>

    <div class="col-sm-12 col-md-2 col-lg-2">
        <?php include view("user_options", "der_edit"); ?>
    </div>
</div>

<?php include view("home", "footer"); ?>
