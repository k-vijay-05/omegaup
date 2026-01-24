<template>
  <div class="card">
    <div class="text-white bg-primary card-header">
      <div class="card-title h4">
        {{ T.discussionReportsTitle || 'Discussion Reports' }}
      </div>
    </div>
    <div class="card-body">
      <div v-if="isLoading" class="text-center">
        <div class="spinner-border" role="status">
          <span class="sr-only">{{ T.wordsLoading || 'Loading...' }}</span>
        </div>
      </div>
      <div v-else>
        <div v-if="reports.length === 0" class="alert alert-info">
          {{ T.discussionReportsEmpty || 'No open reports found.' }}
        </div>
        <div v-else>
          <table class="table table-striped">
            <thead>
              <tr>
                <th>{{ T.wordsReporter || 'Reporter' }}</th>
                <th>{{ T.qualityNominationCreatedBy || 'Created by' }}</th>
                <th>{{ T.wordsDate || 'Date' }}</th>
                <th>{{ T.wordsReason || 'Reason' }}</th>
                <th>{{ T.wordsActions || 'Actions' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in reports" :key="report.report_id">
                <td>
                  {{
                    (report.reporter && report.reporter.username) ||
                    T.wordsUnknown ||
                    'Unknown'
                  }}
                </td>
                <td>
                  {{
                    (report.author && report.author.username) ||
                    T.wordsUnknown ||
                    'Unknown'
                  }}
                </td>
                <td>{{ time.formatDate(report.created_at) }}</td>
                <td>
                  {{ getReasonLabel(report.reason) }}
                </td>
                <td>
                  <a
                    class="btn btn-sm btn-link"
                    href="#"
                    @click.prevent="showDetails(report.report_id)"
                  >
                    {{ T.wordsDetails || 'Details' }}
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
          <omegaup-common-paginator
            :pager-items="pagerItems"
            class="mb-3"
            @page-changed="$emit('page-change', $event)"
          ></omegaup-common-paginator>
        </div>
      </div>
    </div>

    <b-modal
      v-model="showDetailsModal"
      :title="T.wordsDetails || 'Details'"
      size="lg"
      static
      lazy
      @hide="showDetailsModal = false"
    >
      <div v-if="selectedReport">
        <div class="mb-3">
          <strong>{{ T.wordsReason || 'Reason' }}:</strong>
          <div class="mt-2">
            <div class="mb-2">
              <span class="badge badge-primary">
                {{ getReasonLabel(selectedReport.reason) }}
              </span>
            </div>
            <div
              v-if="getReasonFullText(selectedReport.reason)"
              class="border rounded p-2 bg-light"
              style="word-wrap: break-word; white-space: pre-wrap"
            >
              {{ getReasonFullText(selectedReport.reason) }}
            </div>
          </div>
        </div>
        <div v-if="selectedReport.reply" class="mb-3">
          <strong>{{ T.wordsReply || 'Reply' }}:</strong>
          <div
            class="border rounded p-2 bg-light mt-2"
            style="word-wrap: break-word; white-space: pre-wrap"
          >
            {{
              (selectedReport.reply && selectedReport.reply.content) ||
              T.replyDeleted ||
              'Reply deleted'
            }}
          </div>
        </div>
        <div class="mb-3">
          <strong>{{ T.wordsDiscussion || 'Discussion' }}:</strong>
          <div
            class="border rounded p-2 bg-light mt-2"
            style="word-wrap: break-word; white-space: pre-wrap"
          >
            {{
              (selectedReport.discussion &&
                selectedReport.discussion.content) ||
              T.discussionDeleted ||
              'Discussion deleted'
            }}
          </div>
        </div>
      </div>
      <template #modal-footer>
        <div class="w-100 d-flex justify-content-end">
          <button
            class="btn btn-danger mr-2"
            type="button"
            :disabled="!selectedReport || !hasContent(selectedReport)"
            @click="handleDeleteFromModal"
          >
            {{ T.wordsDelete || 'Delete' }}
          </button>
          <button
            class="btn btn-secondary"
            type="button"
            @click="handleDismissFromModal"
          >
            {{ T.discussionReportDismiss || 'Dismiss' }}
          </button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Emit } from 'vue-property-decorator';
import T from '../../lang';
import * as time from '../../time';
import { messages, types } from '../../api_types';
import common_Paginator from '../common/Paginator.vue';

import 'bootstrap-vue/dist/bootstrap-vue.css';
import { ModalPlugin } from 'bootstrap-vue';
Vue.use(ModalPlugin);

@Component({
  components: {
    'omegaup-common-paginator': common_Paginator,
  },
})
export default class DiscussionReports extends Vue {
  @Prop() reports!: messages.ProblemDiscussionListReportsResponse['reports'];
  @Prop() total!: number;
  @Prop() page!: number;
  @Prop() pageSize!: number;
  @Prop() isLoading!: boolean;
  @Prop() pagerItems!: types.PageItem[];

  T = T;
  time = time;
  showDetailsModal = false;
  selectedReport:
    | messages.ProblemDiscussionListReportsResponse['reports'][0]
    | null = null;

  getReasonLabel(reason: string): string {
    if (!reason) return T.wordsUnknown || 'Unknown';
    // Extract the dropdown reason (part before ":")
    const colonIndex = reason.indexOf(':');
    if (colonIndex > 0) {
      const label = reason.substring(0, colonIndex).trim();
      // Map reason codes to user-friendly labels
      const reasonMap: { [key: string]: string } = {
        offensive:
          T.reportDiscussionFormOffensive ||
          'It is offensive or inappropriate.',
        spam: T.reportDiscussionFormSpam || 'It is spam.',
        'poorly-described':
          T.reportDiscussionFormPoorlyDescribed ||
          'It is poorly described or unclear.',
        'off-topic':
          T.reportDiscussionFormOffTopic || 'It is off-topic or not relevant.',
        duplicate:
          T.reportDiscussionFormDuplicate ||
          'It is a duplicate of another discussion/reply.',
        other: T.reportDiscussionFormOtherReason || 'Other reason.',
      };
      return reasonMap[label] || label;
    }
    // If no colon, check if it's a known reason code
    const reasonMap: { [key: string]: string } = {
      offensive:
        T.reportDiscussionFormOffensive || 'It is offensive or inappropriate.',
      spam: T.reportDiscussionFormSpam || 'It is spam.',
      'poorly-described':
        T.reportDiscussionFormPoorlyDescribed ||
        'It is poorly described or unclear.',
      'off-topic':
        T.reportDiscussionFormOffTopic || 'It is off-topic or not relevant.',
      duplicate:
        T.reportDiscussionFormDuplicate ||
        'It is a duplicate of another discussion/reply.',
      other: T.reportDiscussionFormOtherReason || 'Other reason.',
    };
    return reasonMap[reason.trim()] || reason.trim();
  }

  getReasonFullText(reason: string): string {
    if (!reason) return '';
    // Extract the full text after the reason label
    const colonIndex = reason.indexOf(':');
    if (colonIndex > 0) {
      return reason.substring(colonIndex + 1).trim();
    }
    return '';
  }

  getReplyId(
    report: messages.ProblemDiscussionListReportsResponse['reports'][0],
  ): number | null {
    return (report as any).reply_id || null;
  }

  hasContent(
    report: messages.ProblemDiscussionListReportsResponse['reports'][0] | null,
  ): boolean {
    if (!report) return false;
    return !!(report.discussion || (report as any).reply);
  }

  showDetails(reportId: number): void {
    const report = this.reports.find((r) => r.report_id === reportId);
    if (report) {
      this.selectedReport = report;
      this.showDetailsModal = true;
    }
  }

  handleDeleteFromModal(): void {
    if (!this.selectedReport) return;
    this.showDetailsModal = false;
    const replyId = this.getReplyId(this.selectedReport);
    this.onDelete(
      this.selectedReport.report_id,
      this.selectedReport.discussion_id,
      replyId,
    );
  }

  handleDismissFromModal(): void {
    if (!this.selectedReport) return;
    this.showDetailsModal = false;
    this.onDismiss(this.selectedReport.report_id);
  }

  @Emit('delete-discussion')
  onDelete(
    reportId: number,
    discussionId: number,
    replyId?: number | null,
  ): { reportId: number; discussionId: number; replyId?: number | null } {
    return { reportId, discussionId, replyId };
  }

  @Emit('resolve-report')
  onDismiss(reportId: number): { reportId: number; status: 'dismissed' } {
    return { reportId, status: 'dismissed' };
  }
}
</script>

<style scoped>
.badge {
  font-size: 0.875rem;
  padding: 0.25rem 0.5rem;
}
</style>
