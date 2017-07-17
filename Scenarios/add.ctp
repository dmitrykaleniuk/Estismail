<?php $this->append('css_top');?>
<link rel="stylesheet" type="text/css" href="/assets/plugins/select2/select2.css">
<link href="/assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="/assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
<?php $this->end();?>
<div class="portlet light bordered form-fit">
    <div class="portlet-title">
        <div class="caption caption-md">
            <i class="fa fa-plus font-green-dark"></i>
            <span class="caption-subject font-green-dark bold uppercase"><?php echo __('Создать сценарий'); ?></span>
        </div>
        <div class="actions">
            <a href=""
               class="btn btn-sm btn-circle btn-default easy-pie-chart-reload">
                <i class="icon-refresh"></i> <?php echo __('Обновить'); ?> </a>
        </div>
    </div>
    <div class="portlet-body form">
        <?php echo $this->element('flash/main'); ?>
        <form class="form-horizontal form-bordered" role="form" method="post">
            <div class="form-body">
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo __('Название'); ?></label>
                    <div class="col-md-10">
                        <?php echo $this->Form->input('Scenario.title',
                        array(
                        'div'         => false,
                        'label'       => false,
                        'class'       => 'form-control',
                        'type'        => 'text',
                        'placeholder' => __('Введите название сценария'),
                        'required'    => true
                        )
                        ); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo __('Емейл отправителя'); ?></label>
                    <div class="col-md-10">
                        <?php echo $this->Form->select('Scenario.user_setting_id',
                        $sender_emails,
                        array(
                        'class' => 'form-control',
                        'empty' => false,
                        'default' => $default_email
                        )
                        ); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo __('Название письма'); ?></label>
                    <div class="col-md-10">
                        <?php echo $this->Form->input('Letter.title',
                        array(
                        'div'         => false,
                        'label'       => false,
                        'class'       => 'form-control',
                        'type'        => 'text',
                        'placeholder' => __('Введите название письма'),
                        'required'    => true
                        )
                        ); ?>
                    </div>
                </div>
                <div class="form-group">
                <label class="col-md-2 control-label"><?php echo __('Название макета'); ?></label>
                <div class="col-md-10">
                    <?php echo $this->Form->select('Maket.id',
                    $makets,
                    array(
                    'empty' => true,
                    'class' => 'form-control select2',
                    'required' => true
                    )
                    ); ?>
                </div>

            </div>
            </div>
            <div class="form-actions">
                <div class="row">
                    <div class="col-md-offset-3 col-md-9">
                        <button type="submit" class="btn green"><?php echo __('Создать'); ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $this->append("scripts_bottom");?>
<script type="text/javascript" src="/assets/global/plugins/select2/js/select2.full.min.js"></script>
<script src="/assets/pages/scripts/components-select2.min.js" type="text/javascript"></script>
<?php $this->end();?>