<?php
require_once __DIR__.'/../vendor/autoload.php';

/** TCPDF's default Error() exits with public diagnostics; use the API error handler instead. */
class RbReportPdf extends TCPDF
{
    public function Error($message)
    {
        throw new RuntimeException('PDF rendering failed: '.$message);
    }
}
