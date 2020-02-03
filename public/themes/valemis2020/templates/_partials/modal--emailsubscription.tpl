<div class="modal fade" id="modalEmailsubscription" tabindex="-1" role="dialog" aria-labelledby="modalEmailsubscriptionLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEmailsubscriptionLabel">S'inscrire à la newsletter</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="{$urls.pages.index}#footer" method="post" class="needs-validation">
          <input type="hidden" name="action" value="0">
          <div class="input-group">
            <input
                    name="email"
                    class="form-control{if isset($nw_error) and $nw_error} is-invalid{/if}"
                    type="email"
                    value="{$value}"
                    placeholder="{l s='Your email address' d='Shop.Forms.Labels'}"
                    aria-labelledby="block-newsletter-label"
                    autocomplete="email"
            >
            <div class="input-group-append">
              <button class="btn btn-primary" type="submit" name="submitNewsletter"><span class="d-none d-sm-inline">{l s='Subscribe' d='Shop.Theme.Actions'}</span><span class="d-inline d-sm-none">{l s='OK' d='Shop.Theme.Actions'}</span></button>
            </div>
          </div>

          <div class="clearfix">
              {if $msg}
                <p class="alert mt-2 {if $nw_error}alert-danger{else}alert-success{/if}">
                    {$msg}
                </p>
              {/if}
              {if $conditions}
                <p class="small mt-2">{$conditions}</p>
              {/if}
              {if isset($id_module)}
                  {hook h='displayGDPRConsent' id_module=$id_module}
              {/if}
          </div>
        </form>
      </div>
    </div>
  </div>
</div>