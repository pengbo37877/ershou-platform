<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class DenyReport
{
    protected $book_id;

    public function __construct($book_id)
    {
        $this->book_id = $book_id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.recover-reports-deny').on('click', function () {

    // Your code.
    console.log($(this).data('id'));
    $.ajax({
        method: 'post',
        url: '/admin/recover_reports/deny',
        data: {
            _token:LA.token,
            book_id: $(this).data('id')
        },
        success: function () {
            $.pjax.reload('#pjax-container');
            toastr.success('操作成功');
        }
    });
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());

        return "<a class='btn btn-xs btn-dark recover-reports-deny' data-id='{$this->book_id}'>不接受反驳</a>";
    }

    public function __toString()
    {
        return $this->render();
    }
}