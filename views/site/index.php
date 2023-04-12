<?php

use yii\bootstrap4\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

$this->title = 'My Yii Application';
?>
<div class="site-index">

    <?php if ($isSetFile) {
        echo 'Файл запущен';
    } else {
        echo 'Ошибка запуска';
    }
    ?>


    <div class="jumbotron text-center bg-transparent">
        <?php $form = ActiveForm::begin(); ?>
        <?php $strBtn = $isSetFile ? 'Остановить' : 'Запустить'; ?>
        <?= Html::submitButton($strBtn, ['class' => 'btn btn-lg btn-success', 'name' => 'login-button', 'id' => 'btn-run']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php
$js = <<<JS


    $('form').on('beforeSubmit', function(){
        $.ajax({
            url: '/site/run',
            type: 'POST',
            success: function(res){
                $('#btn-run').text(res ? 'Остановить' : 'Запустить');
            },
            error: function(){
                alert('Error!');
            }
        });
        return false;
    });
JS;

$this->registerJs($js);
?>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        $('#btn-run')

    })

    function runScript() {
        $.post({
            type: "GET",
            url: '/site/run',
            dataType: "json",
        });
        console.log('fdsfa');
    }
</script>