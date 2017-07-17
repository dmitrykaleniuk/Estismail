<?php $this->append('css_top');?>
<style type="text/css">

    tr.vert-align>td {
        vertical-align: middle !important;
    }

    .table .btn {
        margin-bottom: 0px;
    }

</style>
<?php $this->end();?>


<div class="portlet light portlet-fit bordered">
    <div class="portlet-title">
        <div class="caption">
            <i class="fa icon-user-follow font-green-dark"></i>
            <span class="caption-subject font-green-dark bold uppercase"><?php echo __('Список всех сценариев');?></span>
        </div>
        <div class="actions">
            <a href="" class="btn btn-sm btn-circle btn-default easy-pie-chart-reload">
                <i class="fa fa-repeat"></i> <?php echo __('Обновить ');?></a>
        </div>
    </div>
    <?php if($scenarios):?>
    <div class="portlet-body">
        <?php echo $this->element('flash/main'); ?>
        <div class="table-toolbar">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group">
                        <a href="/scenarios/add" class="btn blue tooltips"><?php echo __('Создать сценарий');?></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-scrollable">
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th><?php echo __('Заголовок');?></th>
                    <th><?php echo __('Количество писем');?></th>
                    <th><?php echo __('Действия');?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($scenarios as $key => $value): ?>
                <tr class="vert-align">
                    <td><?php echo $value['Scenario']['title'];?></td>
                    <td></td>
                    <td>
                        <a href="/scenarios/run/<?php echo $value['Scenario']['id'];?>" class="btn btn-circle btn-icon-only green tooltips" data-original-title="<?php echo __('Запустить');?>">
                            <i class="fa fa-play"></i>
                        </a>
                        <a href="/scenarios/steps/<?php echo $value['Scenario']['id'];?>" class="btn btn-circle btn-icon-only yellow-crusta tooltips" data-original-title="<?php echo __('Шаги сценария');?>">
                            <i class="fa fa-sitemap"></i>
                        </a>
                        <a href="/scenarios/copy/<?php echo $value['Scenario']['id'];?>" class="btn btn-circle btn-icon-only purple tooltips" data-original-title="<?php echo __('Сделать копию');?>">
                            <i class="fa fa-copy"></i>
                        </a>
                        <a href="/scenarios/edit/<?php echo $value['Scenario']['id'];?>" class="btn btn-circle btn-icon-only orange tooltips" data-original-title="<?php echo __('Редактировать');?>">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="/scenarios/delete/<?php echo $value['Scenario']['id'];?>" class="btn btn-circle btn-icon-only red-thunderbird tooltips" onclick="return confirm('<?php echo __('Вы уверены?');?>')" data-original-title="<?php echo __('Удалить сценарий');?>">
                            <i class="icon-trash"></i>
                        </a>

                    </td>
                </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
        <div class="">
            <?php echo $this->Utils->pagination(
            array(
            'page_number'          => $number,
            'total_count'          => $count,
            'per_page'             => $shown,
            'disable_per_page_all' => true
            )
            );?>
        </div>
    </div>
    <?php else: ?>
    <div class="portlet-body">
        <?php echo $this->element('flash/main'); ?>
        <div class="caption">
            <span class="caption-subject font-green-dark bold"><?php echo __('Ниодного сценария еще не создано');?></span>
        </div>
        <div class="actions">
            <div class="btn-group">
                <a href="/scenarios/add" class="btn blue tooltips"><?php echo __('Создать сценарий');?></a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
