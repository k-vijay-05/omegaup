<template>
  <div class="discussion-container">
    <div class="discussion-header mb-4">
      <h3 class="mb-1">
        {{ T.wordsDiscussion || 'Problem Discussion' }}
      </h3>
      <p class="text-muted mb-0">
        {{
          T.discussionDescription ||
          'Share questions, tips, or feedback about this problem.'
        }}
      </p>
    </div>

    <div class="d-flex justify-content-end align-items-center mb-3">
      <label class="mb-0 mr-2 text-muted">{{ T.wordsSort || 'Sort:' }}</label>
      <select
        v-model="selectedSort"
        class="form-control form-control-sm w-auto"
        @change="onSortChanged"
      >
        <option value="created_at">
          {{ T.wordsRecent || 'Recent' }}
        </option>
        <option value="upvotes">
          {{ T.wordsMostUpvotes || 'Most upvotes' }}
        </option>
      </select>
    </div>

    <div class="discussion-posts">
      <div
        v-for="discussion in discussions"
        :key="discussion.discussion_id"
        class="discussion-card mb-3"
      >
        <div class="card-body d-flex align-items-start">
          <div
            :class="`avatar-circle ${getAvatarColor(
              discussion.identity_id,
            )} text-white mr-3`"
          >
            {{ getAvatarInitials(discussion.username) }}
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div>
                <h6 class="mb-0">{{ discussion.username }}</h6>
                <small class="text-muted">{{
                  formatDate(discussion.created_at)
                }}</small>
              </div>
            </div>
            <div
              v-if="editingDiscussionId !== discussion.discussion_id"
              class="mb-3"
            >
              <omegaup-markdown
                :markdown="discussion.content"
              ></omegaup-markdown>
            </div>
            <div v-else class="mb-3">
              <div
                ref="editDiscussionMarkdownButtonBar"
                class="wmd-button-bar"
              ></div>
              <textarea
                ref="editDiscussionMarkdownInput"
                v-model="editDiscussionText"
                class="wmd-input discussion-textarea"
                rows="4"
              ></textarea>
              <div class="d-flex justify-content-end mt-2">
                <button
                  class="btn btn-sm btn-secondary mr-2"
                  @click="cancelEditDiscussion"
                >
                  {{ T.wordsCancel || 'Cancel' }}
                </button>
                <button
                  class="btn btn-sm btn-primary"
                  :disabled="!editDiscussionText.trim()"
                  @click="saveEditDiscussion(discussion.discussion_id)"
                >
                  {{ T.wordsSave || 'Save' }}
                </button>
              </div>
            </div>
            <div class="d-flex align-items-center actions-row">
              <button
                class="btn btn-sm btn-outline-secondary mr-2"
                @click="onUpvote(discussion.discussion_id)"
              >
                <font-awesome-icon :icon="['fas', 'arrow-up']" class="mr-1" />
                {{ discussion.upvotes }}
              </button>
              <button
                class="btn btn-sm btn-outline-secondary mr-3"
                @click="onDownvote(discussion.discussion_id)"
              >
                <font-awesome-icon :icon="['fas', 'arrow-down']" class="mr-1" />
                {{ discussion.downvotes }}
              </button>
              <button
                class="btn btn-sm btn-outline-secondary mr-3"
                @click="openReplies(discussion.discussion_id)"
              >
                <font-awesome-icon
                  :icon="['fas', 'comment-alt']"
                  class="mr-1"
                />
                {{ discussion.reply_count }}
              </button>
              <template v-if="discussion.username === currentUsername">
                <button
                  v-if="editingDiscussionId !== discussion.discussion_id"
                  class="btn btn-sm btn-outline-primary mr-2"
                  @click="
                    startEditDiscussion(
                      discussion.discussion_id,
                      discussion.content,
                    )
                  "
                >
                  <font-awesome-icon :icon="['fas', 'edit']" class="mr-1" />
                  {{ T.wordsEdit || 'Edit' }}
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="onDeleteDiscussion(discussion.discussion_id)"
                >
                  <font-awesome-icon :icon="['fas', 'trash']" class="mr-1" />
                  {{ T.wordsDelete || 'Delete' }}
                </button>
              </template>
              <button
                v-else
                class="btn btn-sm btn-outline-danger"
                @click="onReport(discussion.discussion_id)"
              >
                <font-awesome-icon :icon="['fas', 'flag']" class="mr-1" />
                {{ T.wordsReport || 'Report' }}
              </button>
            </div>

            <div
              v-if="activeThreadCommentId === discussion.discussion_id"
              class="discussion-thread mt-3 pt-3 border-top"
            >
              <div
                v-for="reply in discussionReplies[discussion.discussion_id] ||
                []"
                :key="reply.reply_id"
                class="d-flex align-items-start mb-3"
              >
                <div
                  :class="`avatar-circle ${getAvatarColor(
                    reply.identity_id,
                  )} text-white mr-3`"
                >
                  {{ getAvatarInitials(reply.username) }}
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between mb-1">
                    <strong>{{ reply.username }}</strong>
                    <small class="text-muted">{{
                      formatDate(reply.created_at)
                    }}</small>
                  </div>
                  <div v-if="editingReplyId !== reply.reply_id">
                    <omegaup-markdown
                      :markdown="reply.content"
                    ></omegaup-markdown>
                  </div>
                  <div v-else>
                    <div
                      ref="editReplyMarkdownButtonBar"
                      class="wmd-button-bar"
                    ></div>
                    <textarea
                      ref="editReplyMarkdownInput"
                      v-model="editReplyText"
                      class="wmd-input discussion-textarea"
                      rows="3"
                    ></textarea>
                    <div class="d-flex justify-content-end mt-2">
                      <button
                        class="btn btn-sm btn-secondary mr-2"
                        @click="cancelEditReply"
                      >
                        {{ T.wordsCancel || 'Cancel' }}
                      </button>
                      <button
                        class="btn btn-sm btn-primary"
                        :disabled="!editReplyText.trim()"
                        @click="
                          saveEditReply(
                            discussion.discussion_id,
                            reply.reply_id,
                          )
                        "
                      >
                        {{ T.wordsSave || 'Save' }}
                      </button>
                    </div>
                  </div>
                  <div class="mt-2">
                    <template v-if="reply.username === currentUsername">
                      <button
                        v-if="editingReplyId !== reply.reply_id"
                        class="btn btn-sm btn-outline-primary mr-2"
                        @click="startEditReply(reply.reply_id, reply.content)"
                      >
                        <font-awesome-icon
                          :icon="['fas', 'edit']"
                          class="mr-1"
                        />
                        {{ T.wordsEdit || 'Edit' }}
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="
                          onDeleteReply(
                            discussion.discussion_id,
                            reply.reply_id,
                          )
                        "
                      >
                        <font-awesome-icon
                          :icon="['fas', 'trash']"
                          class="mr-1"
                        />
                        {{ T.wordsDelete || 'Delete' }}
                      </button>
                    </template>
                    <button
                      v-else
                      class="btn btn-sm btn-outline-danger"
                      @click="
                        onReportReply(discussion.discussion_id, reply.reply_id)
                      "
                    >
                      <font-awesome-icon :icon="['fas', 'flag']" class="mr-1" />
                      {{ T.wordsReport || 'Report' }}
                    </button>
                  </div>
                </div>
              </div>

              <div class="mt-2">
                <h6 class="mb-2">
                  {{ T.wordsAddReply || 'Add a reply' }}
                </h6>
                <div ref="replyMarkdownButtonBar" class="wmd-button-bar"></div>
                <textarea
                  ref="replyMarkdownInput"
                  v-model="threadReplyText"
                  class="wmd-input discussion-textarea"
                  rows="4"
                  :placeholder="T.wordsWriteReply || 'Write a reply...'"
                ></textarea>
                <div class="d-flex justify-content-end mt-3">
                  <button
                    class="btn btn-primary btn-sm"
                    :disabled="!threadReplyText.trim()"
                    @click="onPostThreadReply(discussion.discussion_id)"
                  >
                    {{ T.wordsPostReply || 'Post Reply' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="discussion-pagination mt-4 mb-4">
      <nav aria-label="Discussion pagination">
        <ul class="pagination justify-content-center mb-0">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a
              class="page-link pagination-btn"
              href="#"
              aria-label="Previous"
              @click.prevent="goToPage(currentPage - 1)"
            >
              <font-awesome-icon :icon="['fas', 'chevron-left']" />
            </a>
          </li>
          <li
            v-for="page in visiblePages"
            :key="page"
            class="page-item"
            :class="{ active: page === currentPage }"
          >
            <a
              class="page-link pagination-btn"
              :class="{ 'pagination-btn-active': page === currentPage }"
              href="#"
              @click.prevent="goToPage(page)"
            >
              {{ page === -1 ? '...' : page }}
            </a>
          </li>
          <li
            class="page-item"
            :class="{ disabled: currentPage === totalPages }"
          >
            <a
              class="page-link pagination-btn"
              href="#"
              aria-label="Next"
              @click.prevent="goToPage(currentPage + 1)"
            >
              <font-awesome-icon :icon="['fas', 'chevron-right']" />
            </a>
          </li>
        </ul>
      </nav>
    </div>

    <div class="discussion-form mt-4">
      <div class="discussion-card">
        <div class="card-body">
          <h5 class="card-title">
            {{ T.wordsAddComment || 'Add a Comment' }}
          </h5>
          <div class="d-flex flex-column">
            <div ref="commentMarkdownButtonBar" class="wmd-button-bar"></div>
            <textarea
              ref="commentMarkdownInput"
              v-model="newCommentText"
              class="wmd-input discussion-textarea"
              rows="6"
              :placeholder="
                T.wordsShareThoughts ||
                'Share your thoughts or ask a question...'
              "
            ></textarea>
          </div>
          <div class="d-flex justify-content-end mt-3">
            <button
              class="btn btn-primary"
              :disabled="!newCommentText.trim()"
              @click="onPostComment"
            >
              {{ T.wordsPostComment || 'Post Comment' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <omegaup-overlay
      :show-overlay="showReportPopup"
      @hide-overlay="showReportPopup = false"
    >
      <template #popup>
        <omegaup-discussion-report-popup
          v-show="showReportPopup"
          @dismiss="showReportPopup = false"
          @submit="onReportSubmit"
        ></omegaup-discussion-report-popup>
      </template>
    </omegaup-overlay>

    <b-modal
      v-model="showDeleteConfirmationModal"
      :title="deleteConfirmationTitle"
      :ok-title="T.wordsYes || 'Yes'"
      :cancel-title="T.wordsNo || 'No'"
      ok-variant="danger"
      cancel-variant="secondary"
      static
      lazy
      @ok="confirmDelete"
    >
      <p>{{ deleteConfirmationMessage }}</p>
    </b-modal>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Ref, Watch, Emit } from 'vue-property-decorator';
import { types } from '../../api_types';
import T from '../../lang';
import * as time from '../../time';
import * as Markdown from '@/third_party/js/pagedown/Markdown.Editor.js';
import * as markdown from '../../markdown';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
  faArrowUp,
  faArrowDown,
  faCommentAlt,
  faFlag,
  faTrash,
  faEdit,
  faChevronLeft,
  faChevronRight,
} from '@fortawesome/free-solid-svg-icons';
import omegaup_problemMarkdown from './Markdown.vue';
import omegaup_DiscussionReportPopup from './DiscussionReportPopup.vue';
import omegaup_Overlay from '../Overlay.vue';

import 'bootstrap-vue/dist/bootstrap-vue.css';
import { ModalPlugin } from 'bootstrap-vue';
Vue.use(ModalPlugin);

library.add(
  faArrowUp,
  faArrowDown,
  faCommentAlt,
  faFlag,
  faTrash,
  faEdit,
  faChevronLeft,
  faChevronRight,
);

@Component({
  components: {
    FontAwesomeIcon,
    'omegaup-markdown': omegaup_problemMarkdown,
    'omegaup-discussion-report-popup': omegaup_DiscussionReportPopup,
    'omegaup-overlay': omegaup_Overlay,
  },
})
export default class ProblemDiscussion extends Vue {
  @Prop() problemAlias!: string;
  @Prop({ default: () => [] }) discussions!: types.Discussion[];
  @Prop({ default: () => ({}) }) discussionReplies!: {
    [key: number]: types.Reply[];
  };
  @Prop({ default: 0 }) totalDiscussions!: number;
  @Prop({ default: false }) isLoadingDiscussions!: boolean;
  @Prop({ default: '' }) currentUsername!: string;

  // Local UI state
  newCommentText = '';
  selectedSort: 'created_at' | 'upvotes' = 'created_at';
  activeThreadCommentId: number | null = null;
  threadReplyText = '';
  currentPage = 1;
  pageSize = 10;
  editingDiscussionId: number | null = null;
  editingReplyId: number | null = null;
  editDiscussionText = '';
  editReplyText = '';
  showReportPopup = false;
  reportingDiscussionId: number | null = null;
  reportingReplyId: number | null = null;
  showDeleteConfirmationModal = false;
  deletingDiscussionId: number | null = null;
  deletingReplyId: number | null = null;

  // Markdown editors
  commentMarkdownEditor: Markdown.Editor | null = null;
  replyMarkdownEditor: Markdown.Editor | null = null;
  editDiscussionMarkdownEditor: Markdown.Editor | null = null;
  editReplyMarkdownEditor: Markdown.Editor | null = null;

  @Ref() readonly commentMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly commentMarkdownInput!: HTMLTextAreaElement;
  @Ref() readonly replyMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly replyMarkdownInput!: HTMLTextAreaElement;
  @Ref() readonly editDiscussionMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly editDiscussionMarkdownInput!: HTMLTextAreaElement;
  @Ref() readonly editReplyMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly editReplyMarkdownInput!: HTMLTextAreaElement;

  T = T;
  time = time;

  @Watch('problemAlias')
  onProblemAliasChanged(): void {
    if (this.problemAlias) {
      this.currentPage = 1;
      this.loadDiscussions();
    }
  }

  @Watch('activeThreadCommentId')
  onActiveThreadCommentIdChanged(
    newValue: number | null,
    oldValue: number | null,
  ): void {
    // Destroy old editor when closing thread
    if (oldValue !== null && this.replyMarkdownEditor) {
      // Clean up the old editor if needed
      this.replyMarkdownEditor = null;
    }
    // Initialize new editor when opening thread
    if (newValue !== null) {
      this.loadReplies(newValue);
      this.$nextTick(() => {
        this.initializeReplyMarkdownEditor();
      });
    }
  }

  @Emit('load-discussions')
  loadDiscussions(): {
    problem_alias: string;
    page: number;
    page_size: number;
    sort_by: 'created_at' | 'upvotes';
    order: string;
  } {
    return {
      problem_alias: this.problemAlias,
      page: this.currentPage,
      page_size: this.pageSize,
      sort_by: this.selectedSort,
      order: this.selectedSort === 'created_at' ? 'DESC' : 'DESC',
    };
  }

  @Emit('post-comment')
  onPostComment(): { problem_alias: string; content: string } | null {
    if (!this.newCommentText.trim() || !this.problemAlias) {
      return null;
    }
    const content = this.newCommentText.trim();
    this.newCommentText = '';
    return {
      problem_alias: this.problemAlias,
      content,
    };
  }

  @Emit('vote')
  onUpvote(discussionId: number): { discussion_id: number; vote_type: string } {
    return {
      discussion_id: discussionId,
      vote_type: 'upvote',
    };
  }

  @Emit('vote')
  onDownvote(
    discussionId: number,
  ): { discussion_id: number; vote_type: string } {
    return {
      discussion_id: discussionId,
      vote_type: 'downvote',
    };
  }

  async openReplies(discussionId: number): Promise<void> {
    if (this.activeThreadCommentId === discussionId) {
      this.activeThreadCommentId = null;
      return;
    }
    this.activeThreadCommentId = discussionId;
    this.threadReplyText = '';
  }

  @Emit('load-replies')
  loadReplies(discussionId: number): { discussion_id: number } {
    return { discussion_id: discussionId };
  }

  @Emit('post-reply')
  onPostThreadReply(
    discussionId: number,
  ): { discussion_id: number; content: string } | null {
    if (!this.threadReplyText.trim() || this.activeThreadCommentId === null) {
      return null;
    }
    const content = this.threadReplyText.trim();
    this.threadReplyText = '';
    return {
      discussion_id: discussionId,
      content,
    };
  }

  onReport(discussionId: number): void {
    this.reportingDiscussionId = discussionId;
    this.reportingReplyId = null;
    this.showReportPopup = true;
  }

  onReportReply(discussionId: number, replyId: number): void {
    this.reportingDiscussionId = discussionId;
    this.reportingReplyId = replyId;
    this.showReportPopup = true;
  }

  onReportSubmit(reason: string): void {
    if (!reason || !reason.trim()) {
      return;
    }
    if (this.reportingDiscussionId === null) {
      return;
    }
    if (this.reportingReplyId !== null) {
      // Report a reply
      this.$emit('report', {
        discussion_id: this.reportingDiscussionId,
        reply_id: this.reportingReplyId,
        reason: reason.trim(),
      });
    } else {
      // Report a discussion
      this.$emit('report', {
        discussion_id: this.reportingDiscussionId,
        reason: reason.trim(),
      });
    }
    // Reset state
    this.reportingDiscussionId = null;
    this.reportingReplyId = null;
  }

  onDeleteDiscussion(discussionId: number): void {
    this.deletingDiscussionId = discussionId;
    this.deletingReplyId = null;
    this.showDeleteConfirmationModal = true;
  }

  onDeleteReply(discussionId: number, replyId: number): void {
    this.deletingDiscussionId = discussionId;
    this.deletingReplyId = replyId;
    this.showDeleteConfirmationModal = true;
  }

  confirmDelete(): void {
    if (this.deletingReplyId !== null && this.deletingDiscussionId !== null) {
      // Delete a reply
      this.$emit('delete-reply', {
        discussion_id: this.deletingDiscussionId,
        reply_id: this.deletingReplyId,
      });
    } else if (this.deletingDiscussionId !== null) {
      // Delete a discussion
      this.$emit('delete-discussion', {
        discussion_id: this.deletingDiscussionId,
      });
    }
    // Reset state
    this.deletingDiscussionId = null;
    this.deletingReplyId = null;
  }

  get deleteConfirmationTitle(): string {
    return this.deletingReplyId !== null
      ? T.wordsDeleteReply || 'Delete Reply?'
      : T.wordsDeleteDiscussion || 'Delete Discussion?';
  }

  get deleteConfirmationMessage(): string {
    return this.deletingReplyId !== null
      ? T.replyDeleteConfirm || 'Are you sure you want to delete this reply?'
      : T.discussionDeleteConfirm ||
          'Are you sure you want to delete this discussion?';
  }

  startEditDiscussion(discussionId: number, content: string): void {
    this.editingDiscussionId = discussionId;
    this.editDiscussionText = content;
    this.$nextTick(() => {
      this.initializeEditDiscussionMarkdownEditor();
    });
  }

  cancelEditDiscussion(): void {
    this.editingDiscussionId = null;
    this.editDiscussionText = '';
  }

  @Emit('update-discussion')
  saveEditDiscussion(
    discussionId: number,
  ): { discussion_id: number; content: string } | null {
    if (!this.editDiscussionText.trim()) {
      return null;
    }
    const content = this.editDiscussionText.trim();
    this.editingDiscussionId = null;
    this.editDiscussionText = '';
    return {
      discussion_id: discussionId,
      content,
    };
  }

  startEditReply(replyId: number, content: string): void {
    this.editingReplyId = replyId;
    this.editReplyText = content;
    this.$nextTick(() => {
      this.initializeEditReplyMarkdownEditor();
    });
  }

  cancelEditReply(): void {
    this.editingReplyId = null;
    this.editReplyText = '';
  }

  @Emit('update-reply')
  saveEditReply(
    discussionId: number,
    replyId: number,
  ): { discussion_id: number; reply_id: number; content: string } | null {
    if (!this.editReplyText.trim()) {
      return null;
    }
    const content = this.editReplyText.trim();
    this.editingReplyId = null;
    this.editReplyText = '';
    return {
      discussion_id: discussionId,
      reply_id: replyId,
      content,
    };
  }

  onSortChanged(): void {
    this.currentPage = 1;
    this.loadDiscussions();
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.totalPages || page === this.currentPage) {
      return;
    }
    this.currentPage = page;
    this.loadDiscussions();
  }

  get totalPages(): number {
    return Math.ceil(this.totalDiscussions / this.pageSize);
  }

  get visiblePages(): number[] {
    const pages: number[] = [];
    const total = this.totalPages;
    const current = this.currentPage;

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

  getAvatarInitials(username: string): string {
    if (!username) return 'U';
    return username
      .split(' ')
      .map((n: string) => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }

  getAvatarColor(identityId: number): string {
    const colors = [
      'bg-primary',
      'bg-success',
      'bg-info',
      'bg-warning',
      'bg-danger',
      'bg-secondary',
    ];
    return colors[identityId % colors.length];
  }

  formatDate(date: Date): string {
    return time.formatDate(date);
  }

  // Markdown editor methods
  initializeMarkdownEditors(): void {
    if (
      this.commentMarkdownButtonBar &&
      this.commentMarkdownInput &&
      !this.commentMarkdownEditor
    ) {
      const markdownConverter = new markdown.Converter({ preview: false });
      this.commentMarkdownEditor = new Markdown.Editor(
        markdownConverter.converter,
        '',
        {
          panels: {
            buttonBar: this.commentMarkdownButtonBar,
            preview: null,
            input: this.commentMarkdownInput,
          },
        },
      );
      this.commentMarkdownEditor.run();
    }
  }

  initializeReplyMarkdownEditor(): void {
    // Handle refs that might be arrays due to v-for
    const buttonBar = Array.isArray(this.replyMarkdownButtonBar)
      ? this.replyMarkdownButtonBar[0]
      : this.replyMarkdownButtonBar;
    const input = Array.isArray(this.replyMarkdownInput)
      ? this.replyMarkdownInput[0]
      : this.replyMarkdownInput;

    if (buttonBar && input) {
      // Destroy existing editor if it exists
      if (this.replyMarkdownEditor) {
        this.replyMarkdownEditor = null;
      }
      const markdownConverter = new markdown.Converter({ preview: false });
      this.replyMarkdownEditor = new Markdown.Editor(
        markdownConverter.converter,
        '',
        {
          panels: {
            buttonBar: buttonBar,
            preview: null,
            input: input,
          },
        },
      );
      this.replyMarkdownEditor.run();
    }
  }

  initializeEditDiscussionMarkdownEditor(): void {
    if (this.editingDiscussionId === null) {
      return;
    }

    // Since only one discussion can be edited at a time, and refs are only created
    // for the element in edit mode (v-else), the refs array will only contain
    // one element at index 0, regardless of which discussion is being edited.
    const buttonBar = Array.isArray(this.editDiscussionMarkdownButtonBar)
      ? this.editDiscussionMarkdownButtonBar[0]
      : this.editDiscussionMarkdownButtonBar;
    const input = Array.isArray(this.editDiscussionMarkdownInput)
      ? this.editDiscussionMarkdownInput[0]
      : this.editDiscussionMarkdownInput;

    if (buttonBar && input) {
      // Destroy existing editor if it exists
      if (this.editDiscussionMarkdownEditor) {
        this.editDiscussionMarkdownEditor = null;
      }
      const markdownConverter = new markdown.Converter({ preview: false });
      this.editDiscussionMarkdownEditor = new Markdown.Editor(
        markdownConverter.converter,
        '',
        {
          panels: {
            buttonBar: buttonBar,
            preview: null,
            input: input,
          },
        },
      );
      this.editDiscussionMarkdownEditor.run();
    }
  }

  initializeEditReplyMarkdownEditor(): void {
    if (this.editingReplyId === null) {
      return;
    }

    // Since only one reply can be edited at a time, and refs are only created
    // for the element in edit mode (v-else), the refs array will only contain
    // one element at index 0, regardless of which reply is being edited.
    const buttonBar = Array.isArray(this.editReplyMarkdownButtonBar)
      ? this.editReplyMarkdownButtonBar[0]
      : this.editReplyMarkdownButtonBar;
    const input = Array.isArray(this.editReplyMarkdownInput)
      ? this.editReplyMarkdownInput[0]
      : this.editReplyMarkdownInput;

    if (buttonBar && input) {
      // Destroy existing editor if it exists
      if (this.editReplyMarkdownEditor) {
        this.editReplyMarkdownEditor = null;
      }
      const markdownConverter = new markdown.Converter({ preview: false });
      this.editReplyMarkdownEditor = new Markdown.Editor(
        markdownConverter.converter,
        '',
        {
          panels: {
            buttonBar: buttonBar,
            preview: null,
            input: input,
          },
        },
      );
      this.editReplyMarkdownEditor.run();
    }
  }

  mounted(): void {
    if (this.problemAlias) {
      this.loadDiscussions();
      this.$nextTick(() => {
        this.initializeMarkdownEditors();
      });
    }
  }

  updated(): void {
    // Re-initialize markdown editors if needed
    this.$nextTick(() => {
      if (
        this.commentMarkdownButtonBar &&
        this.commentMarkdownInput &&
        !this.commentMarkdownEditor
      ) {
        this.initializeMarkdownEditors();
      }
      // Re-initialize reply editor if thread is active but editor is not initialized
      if (this.activeThreadCommentId !== null) {
        const buttonBar = Array.isArray(this.replyMarkdownButtonBar)
          ? this.replyMarkdownButtonBar[0]
          : this.replyMarkdownButtonBar;
        const input = Array.isArray(this.replyMarkdownInput)
          ? this.replyMarkdownInput[0]
          : this.replyMarkdownInput;
        if (buttonBar && input && !this.replyMarkdownEditor) {
          this.initializeReplyMarkdownEditor();
        }
      }
    });
  }
}
</script>

<style lang="scss" scoped>
@import '../../../../sass/main.scss';
@import '../../../../third_party/js/pagedown/demo/browser/demo.css';

.discussion-container {
  background-color: #fff;
  color: #212529;
}

.discussion-header {
  h3 {
    color: #212529;
  }
}

.discussion-card {
  background-color: #fff;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

  .card-body {
    padding: 1rem;
  }
}

.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.actions-row {
  button {
    font-size: 0.875rem;
  }
}

.discussion-thread {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #dee2e6;
}

.discussion-pagination {
  .pagination-btn {
    color: #495057;
    background-color: #fff;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 0.25rem;
    text-decoration: none;

    &:hover:not(.disabled) {
      background-color: #e9ecef;
      border-color: #dee2e6;
    }

    &.pagination-btn-active {
      background-color: #007bff;
      color: #fff;
      border-color: #007bff;
    }
  }

  .page-item.disabled .pagination-btn {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
  }
}

.discussion-form,
.discussion-thread,
.discussion-posts {
  .wmd-button-bar {
    width: 100%;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    padding: 5px;
    min-height: 30px;
  }

  .wmd-input {
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 10px;
    font-family: monospace;
    font-size: 0.875rem;
    min-height: 150px;
    resize: vertical;
    border-bottom-left-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;

    &:focus {
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
  }

  .discussion-textarea {
    background-color: #fff;
    color: #212529;
    border: 1px solid #dee2e6;
    border-top: none;

    &::placeholder {
      color: #6c757d;
    }
  }
}
</style>
