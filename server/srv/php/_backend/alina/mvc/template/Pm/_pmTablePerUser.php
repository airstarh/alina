<?php /** @var $data stdClass | array */

use alina\mvc\View\html;
use alina\Utils\Data;
use alina\Utils\Str;

if (empty($data)) {
    return;
}

if (is_object($data)) $data = [$data];

$counter = 1;

$firstRow = current($data);
$headers  = array_keys((array)$firstRow);

$prevRow = [];
?>
<div class="mt-2 mb-5">
    <table class="bg-black alina-data-table alina-table-stick-header w-pct-100">


        <thead>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>
                <th><?= ___($h) ?></th>
            <?php } ?>
        </tr>
        </thead>


        <tfoot>
        <th>#</th>
        <?php foreach ($headers as $h) { ?>
            <th><?= ___($h) ?></th>
        <?php } ?>
        </tfoot>


        <tbody>
        <?php foreach ($data as $k => $row) { ?>
            <tr>
                <td>
                    <?php if (!is_numeric($k)): ?>
                        <div>
                            <?= ___($k) ?>
                        </div>
                    <?php else: ?>
                        <?= $counter++ ?>
                    <?php endif; ?>
                </td>

                <?php
                //AlinaDebugJson($k);
                //AlinaDebugJson($row);
                ?>

                <?php if (!Data::isIterable($row)): ?>
                    <td>
                        <div><?= $k ?></div>
                        <div><?= $row ?></div>
                    </td>
                <?php else: ?>


                    <?php foreach ($row as $colName => $colValue) { ?>

                        <td class="
                                <?= Str::ifContains($colName, 'date') ? 'text-nowrap' : '' ?>
                            "
                        >
                            <?php if (Data::isIterable($colValue)) { ?>
                                <div><?= $colName ?></div>
                                <?= (new html)->piece('_system/html/_form/table002.php', $colValue) ?>
                            <?php } else { ?>

                                <?php if (
                                    isset($prevRow[$colName])
                                    && !is_numeric($prevRow[$colName])
                                    && $prevRow[$colName] == $colValue
                                ): ?>
                                 ^
                                <?php else: ?>
                                    <?= $colValue ?>
                                <?php endif; ?>

                            <?php } ?>
                        </td>


                    <?php } ?>
                <?php endif; ?>
            </tr>

            <?php
            $prevRow = (array)$row;
            ?>
        <?php } ?>

        </tbody>
    </table>
</div>