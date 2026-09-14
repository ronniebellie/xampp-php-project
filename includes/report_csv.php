<?php
declare(strict_types=1);
require_once __DIR__.'/api_resources.php';

/** Retain numbers; make spreadsheet formulas inert even after leading whitespace. */
function rb_csv_cell($value) {
    if($value===null)return '';
    if(!is_scalar($value))throw new InvalidArgumentException('CSV cell must be scalar');
    if(is_string($value) && preg_match('/^[\s]*[=+@\-]/u',$value)
        && !preg_match('/^-?(?:\d+(?:,\d{3})*)(?:\.\d+)?$/D',$value))return "'".$value;
    return $value;
}
function rb_csv_row($stream,array $cells):void {fputcsv($stream,array_map('rb_csv_cell',$cells));}
function rb_csv_context($stream,string $name,array $data):void {
    rb_csv_row($stream,[$name,'RonBelisle.com']);
    rb_csv_row($stream,['Generated UTC',gmdate('Y-m-d H:i:s')]);
    rb_csv_row($stream,['Basis','USD; nominal unless a column explicitly says present value, discounted or real. Educational model; not a guarantee.']);
    rb_csv_row($stream,['Method','Retain these inputs with the calculator methodology and supported statutory scope. Tax projections use the fixed 2026 model where applicable.']);
    foreach([$data,$data['context']??[],$data['inputs']??[],$data['opts']??[]] as $fields) {
        if(!is_array($fields))continue;
        foreach($fields as $key=>$value)if(is_scalar($value)&&!preg_match('/name|email|token|secret|password|chart|url|summary|text/i',(string)$key))rb_csv_row($stream,[(string)$key,$value]);
    }
    rb_csv_row($stream,[]);
}
