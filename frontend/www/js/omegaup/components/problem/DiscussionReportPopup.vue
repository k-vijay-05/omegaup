<template>
  <omegaup-overlay-popup @dismiss="onHide">
    <transition name="fade">
      <form data-discussion-report-popup class="h-auto w-auto">
        <template v-if="currentView === AvailableViews.Question">
          <div class="form-group">
            <div class="font-weight-bold pb-4">
              {{
                T.reportDiscussionFormQuestion ||
                'Why do you want to report this discussion/reply?'
              }}
            </div>
            <select
              v-model="selectedReason"
              class="control-label w-100"
              name="selectedReason"
            >
              <option value="">
                {{ T.wordsSelect || 'Select a reason...' }}
              </option>
              <option value="offensive">
                {{
                  T.reportDiscussionFormOffensive ||
                  'It is offensive or inappropriate.'
                }}
              </option>
              <option value="spam">
                {{ T.reportDiscussionFormSpam || 'It is spam.' }}
              </option>
              <option value="poorly-described">
                {{
                  T.reportDiscussionFormPoorlyDescribed ||
                  'It is poorly described or unclear.'
                }}
              </option>
              <option value="off-topic">
                {{
                  T.reportDiscussionFormOffTopic ||
                  'It is off-topic or not relevant.'
                }}
              </option>
              <option value="duplicate">
                {{
                  T.reportDiscussionFormDuplicate ||
                  'It is a duplicate of another discussion/reply.'
                }}
              </option>
              <option value="other">
                {{ T.reportDiscussionFormOtherReason || 'Other reason.' }}
              </option>
            </select>
          </div>
          <div v-if="selectedReason == 'duplicate'" class="form-group">
            <label class="control-label w-100">{{
              T.reportDiscussionFormLinkToOriginal ||
              'Link to original discussion/reply (optional)'
            }}</label>
            <input v-model="original" class="w-100" name="original" />
          </div>
          <div class="form-group">
            <label class="control-label w-100">{{
              T.reportDiscussionFormAdditionalComments ||
              'Additional comments (optional)'
            }}</label>
            <textarea
              v-model="rationale"
              class="input-text w-100"
              name="rationale"
              type="text"
              rows="4"
            ></textarea>
          </div>
          <div class="text-right">
            <button
              data-submit-report-button
              class="col-md-4 btn btn-primary"
              type="submit"
              :disabled="
                !selectedReason || (!rationale && selectedReason == 'other')
              "
              @click.prevent="onSubmit"
            >
              {{ T.wordsSend || 'Send' }}
            </button>
          </div>
        </template>
        <template v-if="currentView === AvailableViews.Thanks">
          <div class="w-100 h-100 h3 text-center">
            <h1>
              {{
                T.reportDiscussionFormThanksForReview ||
                'Thanks for your report!'
              }}
            </h1>
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
  rationale = '';
  original = '';
  currentView = AvailableViews.Question;
  selectedReason = '';

  onHide(): void {
    this.$emit('dismiss');
    // Reset state when hiding
    this.rationale = '';
    this.original = '';
    this.selectedReason = '';
    this.currentView = AvailableViews.Question;
  }

  onSubmit(): void {
    if (!this.selectedReason) {
      return;
    }
    if (this.selectedReason === 'other' && !this.rationale.trim()) {
      return;
    }
    // Build the reason string from selected reason and optional comments
    let reason = this.selectedReason;
    if (this.rationale.trim()) {
      reason += ': ' + this.rationale.trim();
    }
    if (this.selectedReason === 'duplicate' && this.original.trim()) {
      reason += ' (Original: ' + this.original.trim() + ')';
    }
    this.$emit('submit', reason);
    this.currentView = AvailableViews.Thanks;
    setTimeout(() => this.onHide(), 2000);
  }
}
</script>
