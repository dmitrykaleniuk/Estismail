<?php $this->append('css_top'); ?>


<?php $this->end(); ?>
<div class="portlet light bordered">
    <div class="portlet-title">
        <div class="caption">
            <i class="icon-badge font-green-dark"></i>
            <span class="caption-subject font-green-dark  bold uppercase"><?php
                echo __('Редактирование платёжной системы') . ' LiqPay';?>
            </span>
        </div>
        <div class="actions">
            <a href="/payments/paymentsystems" class="btn btn-sm green-meadow">
                <i class="icon-action-undo"></i> <?php echo __('Назад');?> </a>
            <a href=""
               class="btn btn-sm btn-circle btn-default easy-pie-chart-reload">
                <i class="fa fa-repeat"></i> <?php echo __('Обновить'); ?> </a>
        </div>
    </div>
    <div class="portlet-body form">
        <!-- BEGIN FORM-->
        <form action=""
              method="post"
              enctype="multipart/form-data"
              class="form-horizontal">
            <div class="form-body">
                <div class="form-group">
                    <label class="col-md-3 control-label"><?php echo __('Public_key');?>
                        <span class="required"> * </span>
                    </label>
                    <div class="col-md-6">
	                    <?php echo $this->Form->input('UserPaymentSystemField.1',
		                    array(
			                    'class'               => 'form-control maxlength',
			                    'id'                  => 'maxlength_thresholdconfig',
			                    'type'                => 'text',
			                    'div'                 => false,
			                    'label'               => false,
			                    'placeholder'         => __('Номер кошелька'),
			                    'data-original-title' => __('Номер кошелька'),
			                    'maxlength'           => 9,
			                    'required'            => true
		                    )
	                    ); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-md-3"><?php echo __('Private_key');?>
                        <span class="required"> * </span>
                    </label>
                    <div class="col-md-6">
	                    <?php echo $this->Form->input('UserPaymentSystemField.2',
		                    array(
			                    'class'               => 'form-control maxlength',
			                    'id'                  => 'maxlength_thresholdconfig',
			                    'type'                => 'text',
			                    'div'                 => false,
			                    'label'               => false,
			                    'placeholder'         => __('Секретный ключ'),
			                    'data-original-title' => __('Секретный ключ'),
			                    'maxlength'           => 16,
			                    'required'            => true
		                    )
	                    ); ?>
                    </div>
                </div>
                <div class="form-actions">
                    <div class="row">
                        <div class="col-md-offset-3 col-md-4">
                            <button  type="submit"  class="btn green tooltips" data-original-title="<?php echo __('Отправить данные');?>"><?php echo __('Submit');?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- END FORM-->

    </div>
</div>
<?$this->append("scripts_bottom");?>

<script src="/assets/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js" type="text/javascript"></script>
<script src="/assets-custom/pages/scripts/components-bootstrap-maxlength.min.js" type="text/javascript"></script>
<?$this->end();?>