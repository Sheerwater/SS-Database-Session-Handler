<?php

class DatabaseSessionHandler
{
    function activate()
    {
        session_set_save_handler(
            array($this, "session_open"),
            array($this, "session_close"),
            array($this, "session_read"),
            array($this, "session_write"),
            array($this, "session_destroy"),
            array($this, "session_gc")
        );
    }

    function __destruct() {
        // objects can't be used on shutdown, so we write the session a little early
        session_write_close();
    }

    function session_open($sessionPath, $sessionName)
    {
        return true;
    }

    function session_close()
    {
        return true;
    }

    function session_read($sessionId)
    {
        // On the first build after adding this module, the required table won't exist. Trying to query on it
        // will generate a user error that will stop the build. If we are building, are in dev mode, and the table
        // doesn't exist, we skip the query
        if (substr($_GET['url'], 0, 10) == '/dev/build'
            and Director::isDev()
            and !DB::getConn()->hasTable('DatabaseSessionStore')
        ) {
            return '';
        }

        $session = DatabaseSessionStore::get()->filter('SessionId', $sessionId)->first();
        if ($session and $session->exists()) {
            return base64_decode($session->Data);
        } else {
            return '';
        }
    }

    function session_write($sessionId, $data)
    {
        $session = DatabaseSessionStore::get()->filter('SessionId', $sessionId)->first();
        if (is_null($session)) {
            $session            = DatabaseSessionStore::create();
            $session->SessionId = $sessionId;
        }

        $session->update([
            'Data' => base64_encode($data),
            'IP'   => $_SERVER['REMOTE_ADDR']
        ]);
        $session->write();

        return true;
    }

    function session_destroy($sessionId)
    {
        $dbSessionId = Convert::raw2sql($sessionId);
        DatabaseSessionStore::get()->removeByFilter("\"SessionId\" = '$dbSessionId'");

        return true;
    }

    function session_gc($sessionMaxLife)
    {
        $sessionMaxLife = (int)$sessionMaxLife;
        DatabaseSessionStore::get()->filter(
            'LastEdited:LessThan', SS_Datetime::create()->setValue("-$sessionMaxLife seconds"
        ))->removeAll();

        return true;
    }
}

