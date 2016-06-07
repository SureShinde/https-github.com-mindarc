

    MP.Backup.Item = Class.create();
    MP.Backup.Item.prototype = {
        <?php
        foreach($_backupItem as $key => $value) {
            echo $key . ': ' . (is_string($value) ? ($value ? $value : "''") : ($value ? $value : "0")) . ",\n";
        }
        ?>

        initialize: function (obj) {
            <?php
            foreach($_backupItem as $key => $value) {
                echo 'this.' . $key . ' = ' . (is_string($value) ? ($value ? $value : "''") : ($value ? $value : "0")) . ";\n";
            }
            ?>

            if (Object.isUndefined(obj)) {
                return;
            }

            for (var prop in obj) {
                this[prop] = obj[prop];
            }
        },

        <?php
        foreach($_backupItem as $key => $value) {
            echo 'get' . ucfirst($key) . ': function () {' . "\n";
            echo "\t" . 'return this.' . $key . ";\n";
            echo '}' . ",\n\n";
        }
        ?>

        isError: function () {
            return Object.isString(this.getError()) && this.getError() != '';
        }
    };

