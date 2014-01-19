<?php

/**
 * A table for storing session session information in the database
 * @property string SessionId
 * @property string Data
 * @property string IP
 */
class DatabaseSessionStore extends DataObject
{
    private static $db = [
        'SessionId' => 'Varchar',
        'Data'      => 'Text',
        'IP'        => 'Varchar(45)'

    ];
    private static $indexes = [
        'SessionId' => [
            'type'  => 'unique',
            'value' => 'SessionId'
        ]
    ];
}
