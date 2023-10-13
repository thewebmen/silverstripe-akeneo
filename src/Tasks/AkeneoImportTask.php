<?php

namespace WeDevelop\Akeneo\Tasks;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use WeDevelop\Akeneo\Imports\AkeneoImport;

class AkeneoImportTask extends BuildTask
{
    /** @config */
    protected $title = 'Akeneo import';

    /** @config */
    private static string $segment = 'AkeneoImportTask';

    /** @config */
    protected $description = 'Imports Akeneo Categoris, Attributes, Family and Products';

    public function run($request)
    {
        /** @var  AkeneoImport $import */
        $import = Injector::inst()->get('AkeneoImport');
        $imports = $request->getVar('import');
        $import->run($imports ? explode(',', $imports) : []);
    }
}
