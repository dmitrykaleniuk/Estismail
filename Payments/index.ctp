<div class="portlet light portlet-fit bordered">
	<div class="portlet-title">
		<div class="caption">
			<i class="icon-wallet font-green-dark"></i>
			<span class="caption-subject font-green-dark bold uppercase"><?php echo __('Платежные системы'); ?></span>
		</div>
		<div class="actions">
			<a href="" class="btn btn-sm btn-circle btn-default easy-pie-chart-reload">
				<i class="fa fa-repeat"></i> <?php echo __('Обновить'); ?>
			</a>
		</div>
	</div>
	<div class="portlet-body">
		<div class="table-container">
			<table class="table table-striped table-bordered table-hover">
				<thead>
					<tr>
						<th>IMG</th>
						<th><?php echo __('Описание'); ?></th>
						<th><?php echo __('Валюта'); ?></th>
						<th><?php echo __('Статус'); ?></th>
						<th><?php echo __('Действия'); ?></th>
						<th><?php echo __('Дополнительно'); ?></th>
					</tr>

				</thead>
				<tbody>
					<?php foreach ($payment_systems as $key => $value): ?>
						<tr>
							<td><img src="<?php echo $value['PaymentSystem']['icon'];?>" class="icon-pay-mini"/></td>
							<td><?php echo $value['PaymentSystem']['description']; ?></td>
							<td><?php echo $value['Currency']['icon']; ?></td>
							<td>

								<?php echo $this->Form->checkbox('UsersPaymentSystem.status', array(
									'div' => false,
									'label' => false,
									'checked' => (bool) $user_payment_system[$value['PaymentSystem']['id']]['UsersPaymentSystem']['status'],
									'class' => 'make-switch',
									'data-payment-system-id' => $value['PaymentSystem']['id'],
									'disabled' => empty($user_payment_system[$value['PaymentSystem']['id']])
								)); ?>

							<td>
								<?php if (empty($user_payment_system[$value['PaymentSystem']['id']])):?>

									<a href="/payments/add/<?php echo $value['PaymentSystem']['id'];?>" class="btn btn-xs green-jungle">
										<?php echo __('Подключить');?>
									</a>

								<?php else:?>

									<a href="/payments/edit/<?php echo $value['PaymentSystem']['id'];?>" class="btn btn-xs blue">
										<?php echo __('Редактировать');?>
									</a>

								<?php endif;?>

							</td>
							<td>
								<a href="<?php echo $value['PaymentSystem']['instructions'];?>" class="btn btn-xs btn-warning"><?php echo __('Инструкция');?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php $this->append('scripts_bottom');?>

<script type="text/javascript">

	$(document).ready(function() {

		$('.make-switch').on('switchChange.bootstrapSwitch', function(event, state) {

			//console.log(state);

			//return;
			var sw = $(this);
			sw.bootstrapSwitch('toggleState', true);

			var blockElement = $(this).parents('table');
			var id = $(this).attr('data-payment-system-id');

			App.blockUI({
				target: blockElement,
				animate: true
			});

			$.ajax({
				url: '/payments/statustoggle/' + id,
				method: 'post',
				dataType: 'json',
				success: function() {
					sw.bootstrapSwitch('state', true, true);
				},
				error: function() {
					sw.bootstrapSwitch('state', false, true);
				},
				complete: function() {
					App.unblockUI(blockElement);
				}
			});


		});

	});

</script>

<?php $this->end();?>