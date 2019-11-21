{*
 * NOTICE OF LICENSE
 *
 * @author    202 ecommerce <tech@202-ecommerce.com>
 * @author    Mondial Relay
 * @copyright Copyright (c) Mondial Relay
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *}
 
<!-- start process monitor modal -->
<div class="modal fade" id="mondialrelayProcessModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{l s='Processing cron: ' mod='mondialrelay'} <span class="cron-name"></span></h4>
      </div>
      <div class="modal-body">
        <div class="loading-step text-center">
          <p>{l s='Loading...' mod='mondialrelay'}</p>
          <i class="icon-refresh icon-spin icon-fw process-loading"></i>
        </div>
        <div class="complete-step hidden text-center">
          {l s='The task is completed. Please see logs tab for more informations.' mod='mondialrelay'}
        </div>
        <div class="error-step hidden text-center">
          <span class="generic-error">
            {l s='The task return an error. Please see logs tab for more informations.' mod='mondialrelay'}
          </span>
          <span class="custom-error"></span>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 0;">
            0%
          </div>
        </div>
      </div>
      <div class="modal-footer hidden">
        <button type="button" class="btn btn-default close-btn" data-dismiss="modal">Close</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- end process monitor modal -->