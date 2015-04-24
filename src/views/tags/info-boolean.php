<p class="form-control-static">
    <?php
        if ($item->$name) {
            echo "<span class='glyphicon glyphicon-ok'></span>&nbsp;{$field['true']}";
        } else {
            echo "<span class='glyphicon glyphicon-remove'></span>&nbsp;{$field['false']}";
        }
    ?>
</p>