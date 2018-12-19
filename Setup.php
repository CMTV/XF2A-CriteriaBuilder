<?php

namespace CMTV\CriteriaBuilder;

use CMTV\CriteriaBuilder\Constants as C;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function postInstall(array &$stateChanges)
    {
        \XF::app()->jobManager()->enqueueUnique('CMTV_CB_addon_data', 'XF:AddOnData', [
            'addon_id' => 'CMTV/CriteriaBuilder'
        ]);
    }

    /* ============================================================================================================== */
    /* INSTALLATION */
    /* ============================================================================================================== */

    /** Creating "xf_cmtv_cb_criterion" table */
    public function installStep1()
    {
        $this->schemaManager()->createTable(C::DB_PREFIX('criterion'), function (Create $table)
        {
            $table->addColumn('criterion_id', 'varchar', 50)->primaryKey();
            $table->addColumn('criteria_type', 'varchar', 100);
            $table->addColumn('code', 'mediumtext');
            $table->addColumn('category_id', 'varchar', 50)->setDefault('');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('is_imported', 'tinyint', 3)->setDefault(0);
        });
    }

    /** Creating "xf_cmtv_cb_category" table */
    public function installStep2()
    {
        $this->schemaManager()->createTable(C::DB_PREFIX('category'), function (Create $table)
        {
            $table->addColumn('category_id', 'varchar', 50)->primaryKey();
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('icon', 'varchar', 50)->setDefault('');
            $table->addColumn('criteria', 'int')->setDefault(0);
        });
    }

    /** Creating "xf_cmtv_cb_param_definition" table */
    public function installStep3()
    {
        $this->schemaManager()->createTable(C::DB_PREFIX('param_definition'), function (Create $table)
        {
            $table->addColumn('definition_id', 'varbinary', 50)->primaryKey();
            $table->addColumn('definition_class', 'varchar', 100);
            $table->addColumn('icon', 'varchar', 50)->setDefault('');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('addon_id', 'varbinary', 50)->setDefault('');
            $table->addUniqueKey('definition_class');
        });
    }

    /** Creating "xf_cmtv_cb_param" table */
    public function installStep4()
    {
        $this->schemaManager()->createTable(C::DB_PREFIX('param'), function (Create $table)
        {
            $table->addColumn('criterion_id', 'varchar', 50);
            $table->addColumn('param_id', 'varchar', 50);
            $table->addColumn('definition_id', 'varchar', 50);
            $table->addColumn('options', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addPrimaryKey(['criterion_id', 'param_id']);
        });
    }

    /* ============================================================================================================== */
    /* UNINSTALLATION */
    /* ============================================================================================================== */

    /** Deleting "xf_cmtv_cb_criterion" table */
    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable(C::DB_PREFIX('criterion'));
    }

    /** Deleting "xf_cmtv_cb_category" table */
    public function uninstallStep2()
    {
        $this->schemaManager()->dropTable(C::DB_PREFIX('category'));
    }

    /** Deleting "xf_cmtv_cb_param_definition" table */
    public function uninstallStep3()
    {
        $this->schemaManager()->dropTable(C::DB_PREFIX('param_definition'));
    }

    /** Deleting "xf_cmtv_cb_param" table */
    public function uninstallStep4()
    {
        $this->schemaManager()->dropTable(C::DB_PREFIX('param'));
    }
}