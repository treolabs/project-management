<?php
/**
 * Project Management
 * TreoLabs Premium Module
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <https://treolabs.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace ProjectManagement;

use Espo\Core\Utils\Json;
use Treo\Core\Loaders\Layout;
use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 *
 * @author o.trelin <o.trelin@treolabs.com>
 * @author d.talko <d.talko@treolabs.com>
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        // this value must be less then value in module "Sales"
        return 150;
    }

    /**
     * @inheritdoc
     */
    public function loadLayouts(string $scope, string $name, array &$data)
    {
        // load layout class
        $layout = (new Layout($this->container))->load();

        // prepare file path
        $filePath = $layout->concatPath($this->path . 'app/Resources/layouts', $scope);
        $fileFullPath = $layout->concatPath($filePath, $name . '.json');

        if (file_exists($fileFullPath)) {
            // get file data
            $fileDataJson = $this->container->get('fileManager')->getContents($fileFullPath);
            $fileDataArr = Json::decode($fileDataJson, true);

            // prepare data
            switch ($name) {
                case 'filters':
                case 'massUpdate':
                case 'relationships':
                    // add only new fields
                    foreach ($fileDataArr as $field) {
                        if (!in_array($field, $data)) {
                            $data[] = $field;
                        }
                    }
                    break;

                case 'list':
                case 'listSmall':
                    // merge fields (by "name" parameter)
                    $dataKeys = array_column($data, 'name');
                    $dataAssoc = array_combine($dataKeys, $data);
                    $fileDataKeys = array_column($fileDataArr, 'name');
                    $fileDataAssoc = array_combine($fileDataKeys, $fileDataArr);
                    $data = array_values(array_merge($dataAssoc, $fileDataAssoc));
                    break;

                case 'detail':
                case 'detailSmall':
                    // add only new panels (by "label" parameter)
                    $dataKeys = array_column($data, 'label');
                    foreach ($fileDataArr as $panel) {
                        if (!in_array($panel['label'], $dataKeys)) {
                            $data[] = $panel;
                        }
                    }
                    break;

                default:
                    $data = array_merge_recursive($data, $fileDataArr);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function onLoad()
    {
        /* Expense workflow */
        $expenseWorkflow = new \ProjectManagement\Workflow\Expense($this->container);
        // Status New->Estimated
        $this->container->get('eventManager')->addListener(
            'workflow.Expense_status.transition.New_Estimated',
            [$expenseWorkflow, 'statusTransitionFromNewToEstimated']
        );
        /**/

        /* Issue workflow */
        $issueWorkflow = new \ProjectManagement\Workflow\Issue($this->container);
        // Status New->ToDo
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_status.transition.New_To Do',
            [$issueWorkflow, 'statusTransitionFromNewToToDo']
        );
        // Status [any]->Done
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_status.entered.Done',
            [$issueWorkflow, 'statusEnteredToDone']
        );
        // Status [any]->Frozen
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_status.entered.Frozen',
            [$issueWorkflow, 'statusEnteredToFrozen']
        );
        // Status [any]->Rejected
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_status.entered.Rejected',
            [$issueWorkflow, 'statusEnteredToRejected']
        );
        // Guard event for change Status
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_status.guard',
            [$issueWorkflow, 'guardStatus']
        );

        // Approval Status ToApprove->Approved
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_approvalStatus.transition.To Approve_Approved',
            [$issueWorkflow, 'approvalStatusTransitionFromToApproveToApproved']
        );
        // Approval Status Approved->ToApprove
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_approvalStatus.transition.Approved_To Approve',
            [$issueWorkflow, 'approvalStatusTransitionFromApprovedToToApprove']
        );
        // Guard event for change Approval Status
        $this->container->get('eventManager')->addListener(
            'workflow.Issue_approvalStatus.guard',
            [$issueWorkflow, 'guardApprovalStatus']
        );
        /* end Issue workflow */
    }
}
