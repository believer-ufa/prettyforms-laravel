<p class="form-control-static">
    <?php
        $date = $item->$name;

        if ($date === null) {
            echo 'NULL';
        } else {
            echo Text::humanDateTime($item->$name);
        }
    ?>
</p>