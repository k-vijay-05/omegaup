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
            <div class="mb-3">
              <omegaup-markdown
                :markdown="discussion.content"
              ></omegaup-markdown>
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
              <button
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
                  <omegaup-markdown
                    :markdown="reply.content"
                  ></omegaup-markdown>
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
  faChevronLeft,
  faChevronRight,
} from '@fortawesome/free-solid-svg-icons';
import omegaup_problemMarkdown from './Markdown.vue';

library.add(
  faArrowUp,
  faArrowDown,
  faCommentAlt,
  faFlag,
  faChevronLeft,
  faChevronRight,
);

@Component({
  components: {
    FontAwesomeIcon,
    'omegaup-markdown': omegaup_problemMarkdown,
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

  // Local UI state
  newCommentText = '';
  selectedSort: 'created_at' | 'upvotes' = 'created_at';
  activeThreadCommentId: number | null = null;
  threadReplyText = '';
  currentPage = 1;
  pageSize = 10;

  // Markdown editors
  commentMarkdownEditor: Markdown.Editor | null = null;
  replyMarkdownEditor: Markdown.Editor | null = null;

  @Ref() readonly commentMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly commentMarkdownInput!: HTMLTextAreaElement;
  @Ref() readonly replyMarkdownButtonBar!: HTMLDivElement;
  @Ref() readonly replyMarkdownInput!: HTMLTextAreaElement;

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
  onActiveThreadCommentIdChanged(): void {
    if (this.activeThreadCommentId !== null) {
      this.loadReplies(this.activeThreadCommentId);
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

  @Emit('report')
  onReport(
    discussionId: number,
  ): { discussion_id: number; reason: string } | null {
    const reason = window.prompt(
      T.reportReason || 'Please provide a reason for reporting:',
    );
    if (!reason || !reason.trim()) {
      return null;
    }
    return {
      discussion_id: discussionId,
      reason: reason.trim(),
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
    if (
      this.replyMarkdownButtonBar &&
      this.replyMarkdownInput &&
      !this.replyMarkdownEditor
    ) {
      const markdownConverter = new markdown.Converter({ preview: false });
      this.replyMarkdownEditor = new Markdown.Editor(
        markdownConverter.converter,
        '',
        {
          panels: {
            buttonBar: this.replyMarkdownButtonBar,
            preview: null,
            input: this.replyMarkdownInput,
          },
        },
      );
      this.replyMarkdownEditor.run();
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
.discussion-thread {
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
