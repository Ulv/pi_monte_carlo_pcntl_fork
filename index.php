<?php
/**
 * Расчет числа пи методом Монте-Карло несколькими параллельными процессами с
 * pcntl_fork()
 *
 * @author Vladimir Chmil <vladimir.chmil@gmail.com>
 */
declare(ticks = 1);

final class Worker
{
    protected $children;
    protected $fn = "./data.txt";
    protected $fn_res = "./result.txt";

    public function __construct($children = 5)
    {
        $this->children = $children;
    }

    public function __destruct()
    {
    }

    public function forkProcesses()
    {
        $cpid = - 1;
        for ($i = 0; $i <= $this->children; $i ++) {
            $pid = pcntl_fork();
            if ($pid == - 1) {
                die("could not fork");
            } else if ($pid) {
                pcntl_waitpid($cpid, $status, WUNTRACED);
                if (pcntl_wifexited($status)) {
                    touch($this->fn);
                    $data = file_get_contents($this->fn);

                    if (! empty($data)) {
                        touch($this->fn_res);
                        $res = file_get_contents($this->fn_res);
                        if (empty($res)) {
                            file_put_contents($this->fn_res, $data);
                        } else {
                            file_put_contents($this->fn_res, ($res + $data) / 2);
                        }
                        unlink($this->fn);
                    }
                }

                time_nanosleep(0, 500);
            } else {
                $cpid = pcntl_fork();

                if ($cpid == - 1) {
                    die("could not fork in child process");
                }

                if (! $cpid) {
                    file_put_contents('./data.txt',
                                      $this->doMonteCarlo(150000));

                    exit(0);
                }
            }
        }
    }

    protected function doMonteCarlo($numTrials)
    {
        $x = $y = 0;

        $r     = 46340;
        $r_pow = pow($r, 2);
        $pass  = 0;

        for ($i = 0; $i < $numTrials; $i ++) {
            $x = mt_rand(0, $r + 1);
            $y = mt_rand(0, $r + 1);

            if (pow($x, 2) + pow($y, 2) < $r_pow) {
                $pass ++;
            }
        }

        return $pass / $i * 4;
    }
}

$w = new Worker(5);
$w->forkProcesses();
