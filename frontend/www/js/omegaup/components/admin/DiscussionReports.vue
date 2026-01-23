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
                <th>{{ T.wordsReportId || 'Report ID' }}</th>
                <th>{{ T.wordsDiscussion || 'Discussion' }}</th>
                <th>{{ T.wordsReason || 'Reason' }}</th>
                <th>{{ T.wordsReporter || 'Reporter' }}</th>
                <th>{{ T.wordsDate || 'Date' }}</th>
                <th>{{ T.wordsActions || 'Actions' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in reports" :key="report.report_id">
                <td>{{ report.report_id }}</td>
                <td>
                  <div class="discussion-preview">
                    <div
                      class="text-truncate"
                      style="max-width: 300px"
                      :title="
                        (report.discussion && report.discussion.content) || ''
                      "
                    >
                      {{
                        (report.discussion && report.discussion.content) ||
                        T.discussionDeleted ||
                        'Discussion deleted'
                      }}
                    </div>
                    <small class="text-muted">
                      {{ T.wordsProblemId || 'Problem ID' }}:
                      {{
                        (report.discussion && report.discussion.problem_id) ||
                        'N/A'
                      }}
                    </small>
                  </div>
                </td>
                <td>
                  <div
                    class="text-truncate"
                    style="max-width: 200px"
                    :title="report.reason"
                  >
                    {{ report.reason }}
                  </div>
                </td>
                <td>
                  {{
                    (report.reporter && report.reporter.username) ||
                    T.wordsUnknown ||
                    'Unknown'
                  }}
                </td>
                <td>{{ time.formatDateTime(report.created_at) }}</td>
                <td>
                  <div class="btn-group" role="group">
                    <button
                      class="btn btn-sm btn-danger"
                      type="button"
                      :disabled="!report.discussion"
                      @click="onDelete(report.report_id, report.discussion_id)"
                    >
                      {{ T.wordsDelete || 'Delete' }}
                    </button>
                    <button
                      class="btn btn-sm btn-secondary"
                      type="button"
                      @click="onDismiss(report.report_id)"
                    >
                      {{ T.discussionReportDismiss || 'Dismiss' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <nav v-if="totalPages > 1" aria-label="Page navigation">
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: page === 1 }">
                <a
                  class="page-link"
                  href="#"
                  @click.prevent="goToPage(page - 1)"
                >
                  {{ T.wordsPrevious || 'Previous' }}
                </a>
              </li>
              <li
                v-for="pageNum in visiblePages"
                :key="pageNum"
                class="page-item"
                :class="{ active: pageNum === page, disabled: pageNum === -1 }"
              >
                <a
                  v-if="pageNum !== -1"
                  class="page-link"
                  href="#"
                  @click.prevent="goToPage(pageNum)"
                >
                  {{ pageNum }}
                </a>
                <span v-else class="page-link">...</span>
              </li>
              <li class="page-item" :class="{ disabled: page === totalPages }">
                <a
                  class="page-link"
                  href="#"
                  @click.prevent="goToPage(page + 1)"
                >
                  {{ T.wordsNext || 'Next' }}
                </a>
              </li>
            </ul>
          </nav>
          <div class="text-center mt-2">
            <small class="text-muted">
              {{ T.wordsShowing || 'Showing' }} {{ startItem }}-{{ endItem }}
              {{ T.wordsOf || 'of' }} {{ total }}
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Emit } from 'vue-property-decorator';
import T from '../../lang';
import * as time from '../../time';
import { messages } from '../../api_types';

@Component
export default class DiscussionReports extends Vue {
  @Prop() reports!: messages.ProblemDiscussionListReportsResponse['reports'];
  @Prop() total!: number;
  @Prop() page!: number;
  @Prop() pageSize!: number;
  @Prop() isLoading!: boolean;

  T = T;
  time = time;

  get totalPages(): number {
    return Math.ceil(this.total / this.pageSize);
  }

  get startItem(): number {
    return (this.page - 1) * this.pageSize + 1;
  }

  get endItem(): number {
    return Math.min(this.page * this.pageSize, this.total);
  }

  get visiblePages(): number[] {
    const pages: number[] = [];
    const total = this.totalPages;
    const current = this.page;

    if (total <= 7) {
      for (let i = 1; i <= total; i++) {
        pages.push(i);
      }
    } else {
      if (current <= 3) {
        for (let i = 1; i <= 4; i++) {
          pages.push(i);
        }
        pages.push(-1); // ellipsis
        pages.push(total);
      } else if (current >= total - 2) {
        pages.push(1);
        pages.push(-1); // ellipsis
        for (let i = total - 3; i <= total; i++) {
          pages.push(i);
        }
      } else {
        pages.push(1);
        pages.push(-1); // ellipsis
        for (let i = current - 1; i <= current + 1; i++) {
          pages.push(i);
        }
        pages.push(-1); // ellipsis
        pages.push(total);
      }
    }
    return pages;
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.totalPages || page === this.page) {
      return;
    }
    this.$emit('page-change', page);
  }

  @Emit('delete-discussion')
  onDelete(
    reportId: number,
    discussionId: number,
  ): { reportId: number; discussionId: number } {
    return { reportId, discussionId };
  }

  @Emit('resolve-report')
  onDismiss(reportId: number): { reportId: number; status: 'dismissed' } {
    return { reportId, status: 'dismissed' };
  }
}
</script>

<style scoped>
.discussion-preview {
  max-width: 300px;
}
</style>
