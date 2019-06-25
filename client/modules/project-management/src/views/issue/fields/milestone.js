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

Espo.define('project-management:views/issue/fields/milestone', 'project-management:views/fields/link', function (Dep) {

    return Dep.extend({

        createDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:projectId', function () {
                var data = {};
                data[this.idName] = null;
                data[this.nameName] = null;
                this.model.set(data);
            }, this);
        },

        getWhereAdditional: function () {
            var projectId = this.model.get('projectId');
            if (projectId) {
                return [
                    {
                        type: 'inProjectAndParentGroups',
                        attribute: 'projectId',
                        value: this.model.get('projectId')
                    }
                ];
            } else {
                return null;
            }
        }

    });

});
