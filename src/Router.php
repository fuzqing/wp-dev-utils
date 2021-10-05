<?php
namespace Fuzqing\WpDevUtils;
/**
 * Class Router
 * @package Fuzqing\WpDevUtils
 * 新增自定义路由 比如： https://fuzqing.com/download?uuid=xxxxxx-123456-123456-123456-xxxxxx-xxxxxx
 *
 * 使用介绍
 *   $router = new \Fuzqing\WpDevUtils\Router();
 *   $router
 *     ->add(['url'=>'download?uuid=([0-9a-zA-Z-_]{36})','rewrite'=>'index.php?uuid=$matches[1]','position'=>'bottom'],'route')
 *     ->add(['uuid'],'query_vars')
 *     ->add(['query_var'=>'uuid','regex'=>'/[0-9a-zA-Z-_]{36}/'],'handle'=>function() {
 *             echo "hello new rule":
 *      }],'redirect')
 *     ->flush();
 */
class Router
{
    /**
     * @var array
     *
     * $newRules = [
     *     'download'=>[
     *          'download?uuid=(.*?)$' => 'index.php?uuid=$matches[1]'
     *      ],
     *  ]
     */
    private array $newRules = [];

    /**
     * @var array
     *
     * $queryVars = ['uuid'];
     */
    private array $queryVars = [];

    /**
     * 回调函数
     * @var array
     */
    private array $templateRedirect = [];

    /**
     * 刷新路由
     */
    public function flush()
    {
        add_action('init',function(){
            foreach ($this->newRules as $newRule) {
                add_rewrite_rule($newRule['url'],$newRule['rewrite'],$newRule['position']);
            }
        });

       /* add_action('wp_loaded', function() {
            $rules = get_option('rewrite_rules');
            foreach ($this->newRules as $key=>$newRule):
                if (!isset( $rules[$key])):
                    global $wp_rewrite;
                    $wp_rewrite->flush_rules();
                endif;
            endforeach;
        });*/

        add_filter('query_vars',function ($vars){
            foreach ($this->queryVars as $queryVar) {
                array_push($vars, $queryVar);
            } 
            return $vars;
        });

        add_action('template_redirect', function (){
            global $wp_query;
            foreach ($this->templateRedirect as $key=>$item) {
                $query_key =  @$wp_query->query_vars[$key];
                if (preg_match($item['regex'],$query_key,$matches)) {
                    $item['handle']();
                }
            }
        });
    }

    /**
     * @param array $data
     * @param string $type
     * @return $this
     */
    public function add(array $data, string $type='rule'): Router
    {
        switch ($type):

            case 'rule':
                /**
                 * $data = ['url'=>'download?uuid=([0-9a-zA-Z-_]{36})','rewrite'=>'index.php?uuid=$matches[1]','position'=>'bottom']
                 */
                $this->newRules[] = $data;
                break;

            case 'query_vars':
                /**
                 * $data=[
                 *  'uuid','token','name'
                 * ];
                 */
                $this->queryVars = array_merge($this->queryVars,$data);
                break;

            case 'redirect':
                /**
                 * $data = [
                 *  'query_var'=>'uuid',
                 *  'regex'=>'/[0-9a-zA-Z-_]{36}/',
                 *  'handle'=>function(){}
                 * ];
                 */
                $this->templateRedirect[$data['query_var']] = [
                    'regex'=>$data['regex'],
                    'handle'=>$data['handle']
                ];
                break;
        endswitch;

        return $this;
    }

    /**
     * @param array $data  ['query_vars'=>['uuid'],'regex'=>'/[0-9a-zA-Z-_]{36}/'];
     */
    public function remove(array $data)
    {
        // 删除rewrite规则
        add_filter('rewrite_rules_array', function ($rules) use($data) {
            foreach ($rules as $rule => $rewrite ) {
                if (preg_match($data['regex'], $rule)) {
                    unset($rules[$rule]);
                }
            }
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
            return $rules;
        });
        // 删除路由参数
        add_filter('query_vars',function ($vars) use($data) {
            return array_diff($vars, $data['query_vars']);
        });
    }
}
