<div class="modal fade" id="registerDeleteModal" tabindex="-1" role="dialog" aria-labelledby="registerDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerDeleteModalLabel">@lang('buttons.delete')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="font-size:0.98rem; color:#333;">
                <span>{{ trim(__('buttons.want-to-delete')) }}</span>
                <strong class="js-register-delete-title"></strong>
                <span>?</span>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary lc-btn-edit btn-sm" data-dismiss="modal">@lang('buttons.cancel')</button>

                <form method="POST" class="js-register-delete-form" action="">
                    @csrf
                    <input type="hidden" name="confirm" value="yes" />
                    <button type="submit" class="btn btn-lookcrim btn-sm">@lang('buttons.confirm')</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Delete button in register cards (rendered as <button>) */
    button.card-edit-buttons.js-open-register-delete-modal {
        border: none !important;
        outline: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
    }
    button.card-edit-buttons.js-open-register-delete-modal:focus {
        outline: none;
        box-shadow: none;
    }
</style>

<script>
    (function initRegisterDeleteModalBootstrap() {
        function setModalData(registerId, registerTitle) {
            var $modal = window.jQuery ? window.jQuery('#registerDeleteModal') : null;
            if (!$modal || !$modal.length) return;

            var title = registerTitle ? ('\u201C' + registerTitle + '\u201D') : '';
            $modal.find('.js-register-delete-title').text(title);

            var action = "{{ url('/registers') }}/" + registerId + "/delete";
            $modal.find('.js-register-delete-form').attr('action', action);
        }

        function initAfterJQueryLoaded() {
            if (!window.jQuery) {
                window.setTimeout(initAfterJQueryLoaded, 50);
                return;
            }

            window.jQuery(document).on('click', '.js-open-register-delete-modal', function (e) {
                e.preventDefault();
                var $btn = window.jQuery(this);
                var registerId = $btn.attr('data-register-id');
                var registerTitle = $btn.attr('data-register-title');
                setModalData(registerId, registerTitle);
                window.jQuery('#registerDeleteModal').modal('show');
            });

            window.jQuery('#registerDeleteModal').on('hidden.bs.modal', function () {
                var $modal = window.jQuery(this);
                $modal.find('.js-register-delete-title').text('');
                $modal.find('.js-register-delete-form').attr('action', '');
            });
        }

        // Inline scripts in Blade run before the layout loads jQuery/Bootstrap;
        // wait until the full page load, then initialize.
        if (document.readyState === 'complete') {
            initAfterJQueryLoaded();
        } else {
            window.addEventListener('load', initAfterJQueryLoaded);
        }
    })();
</script>
