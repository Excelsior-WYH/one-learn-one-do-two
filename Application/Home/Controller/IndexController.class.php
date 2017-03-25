<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends BaseController {
    private $total = 5;
    private $chooseCount = 2;
    public function index() {
        $data = '一枝理执思服发于法';
        $data = preg_split('/(?<!^)(?!$)/u', $data );
        var_dump($data);
    }

    public function question() {
        $openid = session('openid');
        $users = M('users');
        $user = $users->where(array('openid' => $openid))->find();

        //todo 学习限制

        //访问时检查是否为第二天, 重置状态
        if ($user['date'] != date('Y-m-d', time())) {
            $user['date'] = date('Y-m-d', time());
            $user['time'] = time();
            $user['current'] = 0;
            $user['today_learn_groups'] = 0;
            $user['today_learn_id'] = json_encode(array('choose'=>array(), 'fillblank'=>array()));
        }

        //检查学习题目上限
        if ($user['today_learn_groups'] == 5) {
            $this->ajaxReturn(array(
                'status' => 403,
                'error'  => '每天最多只能学五组题'
            ));
        }

        $currentLearn = json_decode($user['today_learn_id']);
        if ($user['current'] < $this->chooseCount) {
            $data = $this->choose($currentLearn);
        } elseif ($user['current'] >= $this->chooseCount && $user['current'] < $this->total){
            $data = $this->fillblank($currentLearn);
        } else {
            $this->ajaxReturn(array(
                'status' => 500,
                'error' => '当前题目未知'
            ));
        }
        $user['today_learn_id'] = json_encode($currentLearn);
        $user['current'] += 1;
        $data['current'] = $user['current'];
        if ($user['current'] == $this->total) {
            $user['current'] = 0;
            $user['count'] += 1;
            $user['today_learn_groups'] += 1;
        }
        $data['total'] = $this->total;
        $users->where(array('openid' => $openid))->save($user);
        $this->ajaxReturn(array(
            'status' => 200,
            'data'  => $data
        ));
    }

    private function choose(&$currentData) {
        if ($currentData->choose) {
            $map['id'] = array('NOT IN', $currentData->choose);
            $question = M('chooses')->where($map)->order('[RAND]')->find();
        } else {
            $question = M('chooses')->order('[RAND]')->find();
        }
        array_push($currentData->choose, $question['id']);
        $data = array(
            'type' => 'choose',
            'question' => $question['question'],
            'options' => array(
                'a' => $question['a'],
                'b' => $question['b'],
                'c' => $question['c']
            ),
            'answer' => $question['answer']
        );
        return $data;
    }

    private function fillblank(&$currentData) {
        if ($currentData->fillblank) {
            $map['id'] = array('NOT IN', $currentData->fillblank);
            $question = M('fillblank')->where($map)->order('[RAND]')->find();
        } else {
            $question = M('fillblank')->order('[RAND]')->find();
        }
        array_push($currentData->fillblank, $question['id']);
        $options = preg_split('/(?<!^)(?!$)/u', $question['answer']);
        $num = 8 - count($options);
        $cmap['chracter'] =  array('NOT IN', $options);
        $add = M('chracters')->where($cmap)->order('[RAND]')->limit($num)->field('chracter')->select();
        foreach ($add as $v) {
            $options = array_merge($options, array($v['chracter']));
        }
        shuffle($options);
        $data = array(
            'type' => 'fillblank',
            'question_type' => $question['type'],
            'question' => $question['question'],
            'options' => $options,
            'answer' => $question['answer']
        );
        if ($question['type'] == 'sigequanmian') {
            $data['image'] = rand(1, 23).'.png';
        }
        return $data;
    }
}