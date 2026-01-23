<template>
  <omegaup-overlay-popup @dismiss="onHide">
    <transition name="fade">
      <form data-discussion-report-popup class="h-auto w-auto">
        <template v-if="currentView === AvailableViews.Question">
          <div class="form-group">
            <div class="font-weight-bold pb-4">
              {{ T.reportReason || 'Please provide a reason for reporting:' }}
            </div>
            <textarea
              v-model="reason"
              class="input-text w-100"
              name="reason"
              type="text"
              rows="4"
              :placeholder="
                T.reportReasonPlaceholder ||
                'Enter the reason for reporting this discussion/reply...'
              "
            ></textarea>
          </div>
          <div class="text-right">
            <button
              data-submit-report-button
              class="col-md-4 btn btn-primary"
              type="submit"
              :disabled="!reason || !reason.trim()"
              @click.prevent="onSubmit"
            >
              {{ T.wordsSend || 'Send' }}
            </button>
          </div>
        </template>
        <template v-if="currentView === AvailableViews.Thanks">
          <div class="w-100 h-100 h3 text-center">
            <h1>{{ T.reportSubmitted || 'Report submitted successfully' }}</h1>
          </div>
        </template>
      </form>
    </transition>
  </omegaup-overlay-popup>
</template>

<script lang="ts">
import { Vue, Component } from 'vue-property-decorator';
import omegaup_OverlayPopup from '../OverlayPopup.vue';
import T from '../../lang';
import * as ui from '../../ui';

export enum AvailableViews {
  Question,
  Thanks,
}

@Component({
  components: {
    'omegaup-overlay-popup': omegaup_OverlayPopup,
  },
})
export default class DiscussionReportPopup extends Vue {
  AvailableViews = AvailableViews;
  T = T;
  ui = ui;
  reason = '';
  currentView = AvailableViews.Question;

  onHide(): void {
    this.$emit('dismiss');
    // Reset state when hiding
    this.reason = '';
    this.currentView = AvailableViews.Question;
  }

  onSubmit(): void {
    if (!this.reason || !this.reason.trim()) {
      return;
    }
    this.$emit('submit', this.reason.trim());
    this.currentView = AvailableViews.Thanks;
    setTimeout(() => this.onHide(), 2000);
  }
}
</script>
