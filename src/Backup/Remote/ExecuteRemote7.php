<?php

namespace DBT\Backup\Remote;

use DBT\Exception\DatabaseDriverNotSupportedException;

class ExecuteRemote7 extends ExecuteRemote6
{

    protected function getDbCredentials()
    {
        $databases = unserialize($this->send("global \$databases; print serialize(\$databases);"));
        $credentials = &$databases['default']['default'];

        if ($credentials['driver'] !== 'mysql') {
            throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $credentials['driver']));
        }

        $credentials['port'] = $credentials['port'] ?: 3306;

        return $credentials;
    }
}
