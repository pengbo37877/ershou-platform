<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/18
 * Time: 10:10
 */

namespace App\Admin\Extensions\Tools;
use Encore\Admin\Grid\Tools\BatchAction;

class GenerateCode extends BatchAction
{
    protected $action;

    public function __construct($action = 1)
    {
        $this->action = $action;
    }

    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {

    $.ajax({
        method: 'post',
        url: '{$this->resource}/generate_code',
        data: {
            _token:LA.token,
            ids: $.admin.grid.selected(),
            action: {$this->action}
        },
        success: function () {
            $.pjax.reload('#pjax-container');
            toastr.success('操作成功');
        }
    });
});

EOT;

    }

    public function render()
    {
        return $this->title;
    }

}