<?php

/**
 * CLI Controlling Module
 *
 * Author Jerry Shaw <jerry-shaw@live.com>
 * Author 秋水之冰 <27206617@qq.com>
 * Author Yara <314850412@qq.com>
 *
 * Copyright 2015-2017 Jerry Shaw
 * Copyright 2017 秋水之冰
 * Copyright 2017 Yara
 *
 * This file is part of NervSys.
 *
 * NervSys is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NervSys is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NervSys. If not, see <http://www.gnu.org/licenses/>.
 */
class ctrl_cli
{
    //Variables
    public static $var = [];

    //Debug type and level
    public static $log = '';
    public static $debug = '';

    //STDIN data
    public static $stdin = '';

    //CLI Command
    private static $cmd = '';

    //Configurations
    private static $cfg = [];

    //PHP Pipe settings
    const setting = [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w']
    ];

    /**
     * Load CLI Configuration
     */
    private static function load_cfg()
    {
        //Check CFG file
        if (is_file(CLI_CFG)) {
            //Load File Controlling Module
            load_lib('core', 'ctrl_file');
            //Get CFG file content
            $json = \ctrl_file::get_content(CLI_CFG);
            if ('' !== $json) {
                //Decode file content and map to CFG
                $data = json_decode($json, true);
                if (isset($data)) self::$cfg = &$data;
                unset($data);
            }
            unset($json);
        }
    }

    /**
     * Create CMD
     */
    private static function build_cmd()
    {
        //Check variables
        if (!empty(self::$var)) {
            //Check specific language in CFG
            if (isset(self::$cfg[self::$var[0]])) {
                //Rebuild all commands
                foreach (self::$var as $k => $v) if (isset(self::$cfg[$v])) self::$var[$k] = self::$cfg[$v];
                //Create command
                self::$cmd = implode(' ', self::$var);
                unset($k, $v);
            }
        }
    }

    /**
     * Get Logs
     *
     * @param string $level
     * @param array $data
     *
     * @return array
     */
    private static function get_logs(string $level, array $data): array
    {
        $logs = [PHP_EOL . date('Y-m-d H:i:s', time())];
        switch ($level) {
            //Log cmd
            case 'cmd':
                $logs[] = 'CMD: ' . self::$cmd;
                break;
            //Log err
            case 'err':
                $logs[] = 'CMD: ' . self::$cmd;
                $logs[] = '' !== $data['ERR'] ? 'ERR: ' . $data['ERR'] : 'ERR: NO ERROR!';
                break;
            //Log all
            case 'all':
                $logs[] = 'CMD: ' . self::$cmd;
                $logs[] = '' !== $data['IN'] ? 'IN:  ' . $data['IN'] : 'IN:  NO INPUT!';
                $logs[] = '' !== $data['OUT'] ? 'OUT: ' . $data['OUT'] : 'OUT: NO OUTPUT!';
                $logs[] = '' !== $data['ERR'] ? 'ERR: ' . $data['ERR'] : 'ERR: NO ERROR!';
                break;
            //No detailed logs
            default:
                break;
        }
        unset($level, $data);
        return $logs;
    }

    /**
     * Run CLI
     * @return array
     */
    public static function run_cli(): array
    {
        //Prepare
        self::load_cfg();
        self::build_cmd();
        //Check command
        if ('' !== self::$cmd) {
            //Run process
            $process = proc_open(self::$cmd, self::setting, $pipes, CLI_WORK_PATH);
            //Parse result
            if (is_resource($process)) {
                //Process STDIN data
                if ('' !== self::$stdin) fwrite($pipes[0], self::$stdin);
                //Parse detailed process data
                $data = ['IN' => self::$stdin, 'OUT' => '', 'ERR' => stream_get_contents($pipes[2])];
                if ('' === $data['ERR']) $data['OUT'] = stream_get_contents($pipes[1]);
                //Save executed result
                $result = ['data' => &$data['OUT']];
                //Process debug and log
                if ('' !== self::$log) \ctrl_file::append_content(CLI_LOG_PATH . 'CLI_LOG_' . date('Y-m-d', time()) . '.txt', implode(PHP_EOL, self::get_logs(self::$log, $data)) . PHP_EOL . PHP_EOL);
                if ('' !== self::$debug) fwrite(STDOUT, implode(PHP_EOL, self::get_logs(self::$debug, $data)) . PHP_EOL . PHP_EOL);
                unset($data);
            } else $result = ['data' => 'Process ERROR!'];
            //Close process
            $result['code'] = proc_close($process);
            unset($process, $pipes);
        } else $result = ['data' => 'Command ERROR!', 'code' => -1];
        return $result;
    }
}