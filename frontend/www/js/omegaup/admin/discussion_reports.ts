import admin_DiscussionReports from '../components/admin/DiscussionReports.vue';
import { OmegaUp } from '../omegaup';
import * as api from '../api';
import * as ui from '../ui';
import T from '../lang';
import Vue from 'vue';
import { messages, types } from '../api_types';

OmegaUp.on('ready', () => {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const adminDiscussionReports: any = new Vue({
    el: '#main-container',
    components: {
      'omegaup-admin-discussion-reports': admin_DiscussionReports,
    },
    data: () => {
      return {
        reports: [] as messages.ProblemDiscussionListReportsResponse['reports'],
        total: 0,
        page: 1,
        pageSize: 20,
        isLoading: false,
        pagerItems: [] as types.PageItem[],
      };
    },
    mounted(): void {
      // Load initial data after Vue instance is mounted
      this.loadReports();
    },
    methods: {
      loadReports(): void {
        this.isLoading = true;
        api.ProblemDiscussion.listReports({
          page: this.page,
          page_size: this.pageSize,
        })
          .then((data: any) => {
            this.reports = data.reports;
            this.total = data.total;
            this.pagerItems = data.pager_items || [];
          })
          .catch(ui.apiError)
          .finally(() => {
            this.isLoading = false;
          });
      },
      onDeleteDiscussion(
        reportId: number,
        discussionId: number,
        replyId?: number | null,
      ): void {
        // Delete the discussion or reply
        const deleteParams: any = {
          discussion_id: discussionId,
        };
        if (replyId) {
          deleteParams.reply_id = replyId;
        }

        api.ProblemDiscussion.delete(deleteParams)
          .then(() => {
            // Try to resolve the report (it may already be cascade-deleted)
            // If it fails, that's okay - the discussion/reply was deleted successfully
            return api.ProblemDiscussion.resolveReport({
              report_id: reportId,
              status: 'resolved',
            }).catch(() => {
              // Report might be cascade-deleted, that's fine
              return Promise.resolve();
            });
          })
          .then(() => {
            ui.success(
              replyId
                ? T.replyDeleted || 'Reply deleted successfully'
                : T.discussionDeleted || 'Discussion deleted successfully',
            );
            this.loadReports();
          })
          .catch(ui.apiError);
      },
      onResolveReport(
        reportId: number,
        status: 'resolved' | 'dismissed',
      ): void {
        api.ProblemDiscussion.resolveReport({
          report_id: reportId,
          status: status,
        })
          .then(() => {
            ui.success(
              status === 'resolved'
                ? T.discussionReportResolved || 'Report resolved successfully'
                : T.discussionReportDismissed ||
                    'Report dismissed successfully',
            );
            this.loadReports();
          })
          .catch(ui.apiError);
      },
      onPageChange(page: number): void {
        this.page = page;
        this.loadReports();
      },
    },
    render: function (createElement) {
      const vm = this as any;
      return createElement('omegaup-admin-discussion-reports', {
        props: {
          reports: vm.reports,
          total: vm.total,
          page: vm.page,
          pageSize: vm.pageSize,
          isLoading: vm.isLoading,
          pagerItems: vm.pagerItems,
        },
        on: {
          'delete-discussion': (data: {
            reportId: number;
            discussionId: number;
            replyId?: number | null;
          }) => {
            vm.onDeleteDiscussion(
              data.reportId,
              data.discussionId,
              data.replyId,
            );
          },
          'resolve-report': (data: {
            reportId: number;
            status: 'resolved' | 'dismissed';
          }) => {
            vm.onResolveReport(data.reportId, data.status);
          },
          'page-change': (page: number) => {
            vm.onPageChange(page);
          },
        },
      });
    },
  });
});
