<div class="portlet light bordered form-fit">
    <div class="portlet-title">
        <div class="caption caption-md">
            <i class="fa fa-pencil font-green-dark"></i>
            <span class="caption-subject font-green-dark bold uppercase"><?php echo __('Редактирование сценария'); ?></span>
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
                        'default' => $default_sender
                        )
                        ); ?>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <div class="row">
                    <div class="col-md-offset-3 col-md-9">
                        <button type="submit" class="btn green"><?php echo __('Сохранить'); ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
