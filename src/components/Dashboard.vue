<template>
  <div class="container p-4 grid grid-cols-12 gap-4">
    <div class="col-span-12 lg:col-span-3 bg-white shadow-md p-4 rounded-lg">
      <h3 class="text-xl font-bold mb-4">{{ translations.statistics }}</h3>
      <div class="space-y-3">
        <div class="stat">
          <div class="stat-title">{{ translations.total_tickets }}</div>
          <div class="stat-value">{{ totalTicket }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">{{ translations.new }}</div>
          <div class="stat-value">{{ ticketsNew }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">{{ translations.waiting }}</div>
          <div class="stat-value">{{ ticketsWaiting }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">{{ translations.in_progress }}</div>
          <div class="stat-value">{{ ticketsInProgress }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">{{ translations.resolved }}</div>
          <div class="stat-value">{{ ticketsResolved }}</div>
        </div>
        <div class="stat">
          <div class="stat-title">{{ translations.closed }}</div>
          <div class="stat-value">{{ ticketsClosed }}</div>
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-9 bg-white shadow-md p-4 rounded-lg">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">{{ translations.tickets_list }}</h3>
        <button class="btn btn-primary rounded-full" @click="openTicketModal">
          <i class="fas fa-plus-circle mr-2"></i>
          {{ translations.add_new_ticket }}
        </button>
      </div>

      <div v-if="loading">
        <!-- Skeleton loader -->
        <div
          v-for="n in skeletonRows"
          :key="n"
          class="flex flex-col gap-4 w-full"
        >
          <div class="flex gap-4 items-center">
            <div class="skeleton w-16 h-16 rounded-full shrink-0"></div>
            <div class="flex flex-col gap-4 w-full">
              <div class="skeleton h-4 w-full"></div>
              <div class="skeleton h-4 w-full"></div>
            </div>
          </div>
          <div class="divider my-1"></div>
        </div>
      </div>

      <div v-else class="w-full">
        <div class="flex items-center">
          <label for="perPageSelect" class="mr-2">{{
            translations.show_per_page
          }}</label>
          <select
            id="perPageSelect"
            v-model="perPage"
            @change="perPageChanged"
            class="select select-bordered"
          >
            <option
              v-for="option in perPageOptions"
              :key="option"
              :value="option"
            >
              {{ option }}
            </option>
          </select>
        </div>
        <table class="table w-full">
          <thead>
            <tr>
              <th>{{ translations.title }}</th>
              <th>{{ translations.user }}</th>
              <th>{{ translations.priority }}</th>
              <th>{{ translations.status }}</th>
              <th>{{ translations.actions }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in tickets" :key="ticket.id">
              <td>{{ ticket.title }}</td>
              <td>{{ ticket.user_display_name }}</td>
              <td>{{ ticket.priority_name }}</td>
              <td>
                <span
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-semibold',
                    getStatusLabel(ticket.status).colorClass,
                  ]"
                >
                  {{ getStatusLabel(ticket.status).label }}
                </span>
              </td>
              <td>
                <button
                  @click="viewTicketDetails(ticket)"
                  class="btn btn-sm btn-outline"
                >
                  {{ translations.details }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="join flex justify-center mt-6 pt-4">
          <button
            v-for="pageNumber in paginationButtons"
            :key="pageNumber"
            class="join-item btn"
            :class="{
              'btn-disabled':
                pageNumber === '...' || pageNumber === currentPage,
            }"
            @click="goToPage(pageNumber)"
          >
            {{ pageNumber }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de confirmation pour la suppression -->
    <dialog id="delete-confirmation-modal" class="modal">
      <div class="modal-box">
        <h3 class="text-lg font-bold mb-4">
          {{ translations.confirm_delete_ticket }}
        </h3>
        <p>{{ translations.delete_ticket_warning }}</p>

        <div class="modal-action flex justify-between">
          <button
            @click="closeDeleteConfirmationModal"
            class="btn btn-secondary"
          >
            {{ translations.cancel }}
          </button>
          <button @click="confirmDeleteTicket" class="btn btn-danger">
            {{ translations.confirm_delete }}
          </button>
        </div>
      </div>
    </dialog>

    <dialog id="confirmation-modal" class="modal">
      <div class="modal-box">
        <h3 class="text-lg font-bold mb-4">
          {{ translations.confirm_status_change }}
        </h3>
        <p>
          {{ translations.description_status_change }} "<strong>{{
            selectedStatusLabel
          }}</strong
          >" ?
        </p>

        <div class="modal-action flex justify-between">
          <button @click="closeConfirmationModal" class="btn btn-secondary">
            {{ translations.cancel }}
          </button>
          <button @click="confirmStatusChange" class="btn btn-primary">
            {{ translations.confirm }}
          </button>
        </div>
      </div>
    </dialog>

    <dialog id="ticket-modal" class="modal">
      <div class="modal-box">
        <button
          @click.prevent="closeTicketModal"
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        >
          ✕
        </button>
        <h3 class="text-lg font-bold mb-4">
          {{ translations.add_new_ticket }}
        </h3>

        <form @submit.prevent="createTicket">
          <div
            :class="['wpit-group', { 'input-error': errors.title }]"
            class="form-control mb-4"
          >
            <label class="wpit-label label" for="title">{{
              translations.title
            }}</label>
            <input
              v-model="newTicket.title"
              type="text"
              id="title"
              class="wpit-input input input-bordered"
            />
          </div>

          <div
            :class="['form-control', { 'input-error': errors.content }]"
            class="mb-4"
          >
            <label class="wpit-label label" for="content">{{
              translations.description
            }}</label>
            <div>
              <vue-editor
                v-model="newTicket.content"
                :editorToolbar="toolbarOptions"
              ></vue-editor>
            </div>
          </div>

          <div
            :class="['wpit-group', { 'input-error': errors.assign }]"
            class="form-control mb-4"
          >
            <label class="wpit-label label" for="ticketAssign">{{
              translations.assigned_to
            }}</label>
            <select
              v-model="newTicket.asigned"
              class="wpit-input input input-bordered"
            >
              <option v-for="user in teamUsers" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>

          <div
            :class="['wpit-group', { 'input-error': errors.priority }]"
            class="form-control mb-4"
          >
            <label class="wpit-label label" for="priority">{{
              translations.priority
            }}</label>
            <select
              v-model="newTicket.priority"
              id="priority"
              class="wpit-input input input-bordered"
            >
              <option
                v-for="priority in priorities"
                :key="priority.id"
                :value="priority.id"
              >
                {{ priority.name }}
              </option>
            </select>
          </div>

          <input
            type="file"
            @change="handleFileUpload"
            class="file-input w-full mb-4"
          />

          <div class="modal-action">
            <button type="submit" class="btn btn-primary w-full rounded-full">
              {{ translations.add }}
            </button>
          </div>
        </form>
      </div>
    </dialog>

    <dialog id="ticket-detail" class="modal">
      <div class="modal-box w-full max-w-2xl h-auto">
        <button
          @click="closeTicketDetail"
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        >
          ✕
        </button>

        <div v-if="activeTicket">
          <div v-if="loadingDetails">
            <div class="flex w-full flex-col gap-4">
              <div class="flex items-center gap-4">
                <div class="flex flex-col gap-4">
                  <div class="skeleton h-4 w-28"></div>
                  <div class="skeleton h-4 w-32"></div>
                  <div class="skeleton h-4 w-52"></div>
                </div>
                <div class="skeleton h-32 w-52"></div>
              </div>
            </div>
          </div>
          <div v-else>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-bold mb-4 mt-4">{{ details.title }}</h3>
              <button
                @click="showDeleteConfirmationModal"
                class="btn btn-danger mt-4 mb-4"
              >
                {{ translations.delete_ticket }}
              </button>
            </div>
            <div class="flex justify-between">
              <div>
                <div class="mb-1">
                  <strong>{{ translations.description }} :</strong>
                  <div v-html="details.description"></div>
                </div>
                <div class="mb-1">
                  <strong>{{ translations.priority }} :</strong>
                  {{ details.priority_name }}
                </div>
                <div class="mb-1">
                  <strong>{{ translations.status }} :</strong> {{ statusLabel }}
                </div>
                <div class="mb-1">
                  <strong>{{ translations.created_by }} :</strong>
                  {{ details.author }}
                </div>
                <div class="mb-1" v-if="details.assigned_user_name">
                  <strong>{{ translations.assigned_to }} :</strong>
                  {{ details.assigned_user_name }}
                </div>
                <div class="mb-1">
                  <strong>{{ translations.creation_date }} :</strong>
                  {{ details.date }}
                </div>
              </div>
              <div v-if="details.attachment_url" class="ms-4 min-w-60 max-w-64">
                <a :href="details.attachment_url" target="_blank">
                  <img :src="details.attachment_url" width="100%" />
                </a>
              </div>
            </div>
          </div>
        </div>
        <div v-if="details.ticket_info" class="mb-1">
          <a
            @click="toggleAdditionalInfo"
            href="javascript:void(0)"
            class="text-blue-500 btn btn-xs"
          >
            {{
              showAdditionalInfo
                ? translations.hide_info
                : translations.see_more_info
            }}
          </a>
        </div>

        <!-- Afficher les informations supplémentaires uniquement si elles existent -->
        <div v-if="details.ticket_info && showAdditionalInfo" class="mt-2">
          <ul class="bg-gray-100 p-2 rounded text-sm">
            <li
              v-for="(info, index) in formattedTicketInfo"
              :key="index"
              class="mb-1"
            >
              <strong>{{ info.title }} : </strong>
              <template v-if="info.isUrl">
                <a
                  :href="info.value"
                  target="_blank"
                  class="text-blue-500 underline"
                >
                  {{ info.value }}
                </a>
              </template>
              <template v-else>
                {{ info.value }}
              </template>
            </li>
          </ul>
        </div>
        <div class="divider"></div>
        <div class="wpit-group form-control mb-4">
          <label class="wpit-label label"
            >{{ translations.change_status }} :</label
          >
          <select
            v-model="details.status"
            @change="confirmUpdateStatus(details.id, details.status)"
            class="wpit-input input input-bordered"
          >
            <option value="new">{{ translations.new }}</option>
            <option value="waiting">{{ translations.waiting }}</option>
            <option value="in_progress">{{ translations.in_progress }}</option>
            <option value="resolved">{{ translations.resolved }}</option>
            <option value="closed">{{ translations.closed }}</option>
          </select>
        </div>
        <div class="divider"></div>
        <div v-if="loadingDetails">
          <div class="flex w-full flex-col gap-4">
            <div class="skeleton h-4 w-56"></div>
            <div class="skeleton h-4 w-72"></div>
            <div class="skeleton h-4 w-full"></div>
          </div>
        </div>
        <div v-else class="mt-4">
          <h4 class="text-lg font-bold">{{ translations.comments }}</h4>
          <form
            @submit.prevent="submitComment"
            class="mt-4"
            v-if="activeTicket"
          >
            <textarea
              v-model="newComment"
              class="textarea textarea-bordered w-full"
              :placeholder="translations.add_comment"
            ></textarea>
            <div class="modal-action mt-2">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="!newComment"
              >
                {{ translations.send }}
              </button>
            </div>
          </form>
          <ul class="mt-2 space-y-2">
            <li
              v-for="comment in comments"
              :key="comment.id"
              class="p-4 bg-gray-100 rounded-md"
            >
              <p>{{ comment.comment }}</p>
              <p class="text-sm text-gray-500">
                {{ translations.posted_by }} {{ comment.author }}
                {{ translations.on }} {{ comment.date }}
              </p>
            </li>
          </ul>
        </div>
      </div>
    </dialog>
  </div>
</template>

<script>
import { Notyf } from "notyf";
import { VueEditor } from "vue3-editor";
export default {
  name: "Dashboard",
  components: { VueEditor },
  data() {
    const translations = window.WPIT_Admin.WPIT_trans;
    return {
      loading: false,
      loadingDetails: false,
      skeletonRows: 5,
      priorities: [],
      tickets: [],
      totalTicket: 0,
      ticketsNew: 0,
      ticketsWaiting: 0,
      ticketsResolved: 0,
      ticketsInProgress: 0,
      ticketsClosed: 0,
      newTicket: {
        title: "",
        content: "",
        priority: null,
        asigned: null,
        file: null,
      },
      errors: {
        title: false,
        content: false,
        priority: false,
      },
      teamUsers: [],
      activeTicket: null,
      showAdditionalInfo: false,
      details: [],
      comments: [],
      newComment: "",
      showConfirmationModal: false,
      ticketIdToUpdate: null,
      statusToConfirm: "",
      currentPage: 1,
      perPage: 10,
      perPageOptions: [5, 10, 20, 50],
      totalPages: 1,
      paginationButtons: [],
      statusOptions: [
        {
          value: "new",
          label: translations.new,
          colorClass: "bg-blue-500 text-white",
        },
        {
          value: "in_progress",
          label: translations.in_progress,
          colorClass: "bg-yellow-500 text-white",
        },
        {
          value: "waiting",
          label: translations.waiting,
          colorClass: "bg-orange-500 text-white",
        },
        {
          value: "resolved",
          label: translations.resolved,
          colorClass: "bg-green-500 text-white",
        },
        {
          value: "closed",
          label: translations.closed,
          colorClass: "bg-gray-500 text-white",
        },
      ],
      selectedStatusLabel: "",
      toolbarOptions: [
        ["bold", "italic", "underline", "strike"],
        ["link"],
        [{ list: "ordered" }, { list: "bullet" }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ align: [] }],
        [{ align: "right" }, { align: "center" }, { align: "justify" }],
        ["clean"],
        ["html"],
      ],
    };
  },
  mounted() {
    this.fetchPriorities();
    this.fetchTickets();
    this.initializeNotyf();
    this.fetchTeamUsers();
  },
  watch: {
    currentPage() {
      this.generatePaginationButtons();
    },
    totalPages() {
      this.generatePaginationButtons();
    },
  },
  computed: {
    translations() {
      return window.WPIT_Admin.WPIT_trans;
    },
    statusLabel() {
      const statusOption = this.statusOptions.find(
        (option) => option.value === this.details.status
      );
      return statusOption ? statusOption.label : this.details.status;
    },
    formattedTicketInfo() {
      if (!this.details || !this.details.ticket_info) {
        // Retourner un tableau vide si aucune information n'est disponible
        return [];
      }

      try {
        const info = JSON.parse(this.details.ticket_info);

        const isUrl = (value) => {
          const urlRegex = /^(https?:\/\/[^\s$.?#].[^\s]*)$/i;
          return urlRegex.test(value);
        };

        return [
          {
            title: this.translations.page_url,
            value: info.url || this.translations.no_info_available,
            isUrl: isUrl(info.url),
          },
          {
            title: this.translations.page_title,
            value: info.title || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.user_agent,
            value: info.userAgent || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.browser_language,
            value: info.browserLanguage || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.platform,
            value: info.platform || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.screen_resolution,
            value: info.screenResolution || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.viewport_size,
            value: info.viewportSize || this.translations.no_info_available,
            isUrl: false,
          },
          {
            title: this.translations.referrer,
            value: info.referrer || this.translations.no_referrer,
            isUrl: isUrl(info.referrer),
          },
        ];
      } catch (error) {
        console.error("Error parsing ticket info JSON: ", error);
        return [];
      }
    },
  },
  methods: {
    toggleAdditionalInfo() {
      this.showAdditionalInfo = !this.showAdditionalInfo;
    },
    getStatusLabel(statusValue) {
      const status = this.statusOptions.find(
        (option) => option.value === statusValue
      );
      return status
        ? status
        : { label: statusValue, colorClass: "bg-gray-300 text-black" };
    },
    initializeNotyf() {
      this.notyf = new Notyf({
        duration: 3000,
        position: { x: "right", y: "bottom" },
      });
    },
    fetchTickets(page = 1) {
      this.loading = true;
      fetch(
        `/wp-json/wbugboard/v1/tickets?page=${page}&per_page=${this.perPage}`
      )
        .then((response) => response.json())
        .then((data) => {
          this.tickets = data.tickets;
          this.totalTicket = data.total_tickets;
          this.ticketsNew = data.status_counts.new;
          this.ticketsWaiting = data.status_counts.waiting;
          this.ticketsInProgress = data.status_counts.in_progress;
          this.ticketsResolved = data.status_counts.resolved;
          this.ticketsClosed = data.status_counts.closed;
          this.totalPages = data.total_pages;
          this.currentPage = page;
          this.loading = false;
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des priorités:", error);
          this.notyf.error(
            `Erreur lors de la récupération des tickets: ${error}`
          );
          this.loading = false;
        });
    },
    fetchTeamUsers() {
      fetch("/wp-json/wbugboard/v1/team", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Erreur HTTP");
          }
          return response.json();
        })
        .then((data) => {
          this.teamUsers = data;
        })
        .catch((error) => {
          console.error(
            "Erreur lors de la récupération des utilisateurs:",
            error
          );
        });
    },
    perPageChanged() {
      this.fetchTickets(1);
    },

    generatePaginationButtons() {
      const buttons = [];
      const maxButtons = 10;

      if (this.totalPages <= maxButtons) {
        for (let i = 1; i <= this.totalPages; i++) {
          buttons.push(i);
        }
      } else {
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(this.totalPages, this.currentPage + 2);

        if (this.currentPage <= 3) {
          endPage = maxButtons;
        } else if (this.currentPage >= this.totalPages - 2) {
          startPage = this.totalPages - maxButtons + 1;
        }

        for (let i = startPage; i <= endPage; i++) {
          buttons.push(i);
        }
      }

      this.paginationButtons = buttons;
    },
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.fetchTickets(page);
      }
    },

    fetchPriorities() {
      fetch("/wp-json/wbugboard/v1/priorities")
        .then((response) => response.json())
        .then((data) => {
          this.priorities = data;
        })
        .catch((error) => {
          console.error(
            "Erreur lors de la récupération des priorités :",
            error
          );
          this.notyf.error(
            `Erreur lors de la récupération des priorités : ${error}`
          );
        });
    },

    handleFileUpload(event) {
      this.newTicket.file = event.target.files[0];
    },
    openTicketModal() {
      document.getElementById("ticket-modal").showModal();
    },
    closeTicketModal() {
      document.getElementById("ticket-modal").close();
    },
    confirmUpdateStatus(ticketId, status) {
      this.ticketIdToUpdate = ticketId;
      this.statusToConfirm = status;
      const selectedOption = this.statusOptions.find(
        (option) => option.value === status
      );
      this.selectedStatusLabel = selectedOption ? selectedOption.label : "";

      document.getElementById("confirmation-modal").showModal();
    },
    confirmStatusChange() {
      this.updateTicketStatus(this.ticketIdToUpdate, this.statusToConfirm);
      this.closeConfirmationModal();
    },
    closeConfirmationModal() {
      document.getElementById("confirmation-modal").close();
      this.ticketIdToUpdate = null;
      this.statusToConfirm = "";
    },
    updateTicketStatus(ticketId, status) {
      const formData = new FormData();
      formData.append("status", status);

      fetch(`/wp-json/wbugboard/v1/update-ticket-status/${ticketId}`, {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.notyf.success(this.translations.ticket_updated);
            this.fetchTickets();
          } else {
            console.error(
              "Erreur lors de la mise à jour du statut:",
              data.message
            );
            this.notyf.error(
              `Erreur lors de la récupération des tickets: ${data.message}`
            );
          }
        })
        .catch((error) => {
          console.error("Erreur lors de la mise à jour du statut:", error);
          this.notyf.error(
            `Erreur lors de la récupération des tickets: ${error}`
          );
        });
    },
    validateFields() {
      // Initialiser les erreurs
      this.errors.title = !this.newTicket.title;
      this.errors.content = !this.newTicket.content;
      this.errors.priority = !this.newTicket.priority;

      // Retourner true si tous les champs sont remplis, false sinon
      return (
        !this.errors.title && !this.errors.content && !this.errors.priority
      );
    },
    // Créer un ticket
    createTicket() {
      if (!this.validateFields()) {
        this.notyf.error(this.translations.validation_error); // Message d'erreur global si besoin
        return;
      }
      const formData = new FormData();
      formData.append("title", this.newTicket.title);
      formData.append("content", this.newTicket.content);
      formData.append("priority", this.newTicket.priority);
      formData.append("asigned", this.newTicket.asigned);
      if (this.newTicket.file) {
        formData.append("file", this.newTicket.file);
      }

      fetch("/wp-json/wbugboard/v1/create-ticket", {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.newTicket = {
              title: "",
              content: "",
              priority: null,
              file: null,
            };
            this.errors = { title: false, content: false, priority: false }; // Réinitialiser les erreurs
            this.tickets.unshift(data.ticket);
            this.closeTicketModal();
            this.notyf.success(this.translations.ticket_created_success);
            this.fetchTickets();
          }
        });
    },

    viewTicketDetails(ticket) {
      this.activeTicket = ticket;
      this.fetchDetails(ticket.id);
      this.fetchComments(ticket.id);
      const modal = document.getElementById("ticket-detail");
      modal.showModal();
    },

    closeTicketDetail() {
      const modal = document.getElementById("ticket-detail");
      modal.close();
      this.activeTicket = null;
      this.comments = [];
    },

    fetchDetails(ticketId) {
      this.loadingDetails = true;
      fetch(`/wp-json/wbugboard/v1/get-ticket-details/${ticketId}`)
        .then((response) => response.json())
        .then((data) => {
          this.details = data || [];
          this.loadingDetails = false;
        })
        .catch((error) => {
          console.error(
            "Erreur lors de la récupération des commentaires:",
            error
          );

          this.notyf.error(
            `Erreur lors de la récupération des commentaires: ${error}`
          );
          this.loadingDetails = false;
        });
    },
    // Récupérer les commentaires associés à un ticket
    fetchComments(ticketId) {
      this.loadingDetails = true;
      fetch(`/wp-json/wbugboard/v1/get-ticket-comments/${ticketId}`)
        .then((response) => response.json())
        .then((data) => {
          this.comments = data.comments || [];
          this.loadingDetails = false;
        })
        .catch((error) => {
          console.error(
            "Erreur lors de la récupération des commentaires:",
            error
          );

          this.notyf.error(
            `Erreur lors de la récupération des commentaires: ${error}`
          );
          this.loadingDetails = false;
        });
    },

    // Soumettre un commentaire
    submitComment() {
      const formData = new FormData();
      formData.append("ticket_id", this.activeTicket.id);
      formData.append("comment", this.newComment);

      fetch("/wp-json/wbugboard/v1/add-comment", {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.comments.unshift(data.comment);
            this.newComment = "";
          }
        });
    },
    showDeleteConfirmationModal() {
      document.getElementById("delete-confirmation-modal").showModal();
    },

    // Ferme la modale de confirmation
    closeDeleteConfirmationModal() {
      document.getElementById("delete-confirmation-modal").close();
    },

    // Confirme la suppression et envoie une requête pour supprimer le ticket
    confirmDeleteTicket() {
      if (!this.activeTicket) return;

      fetch(
        `/wp-json/wbugboard/v1/delete-ticket/${this.activeTicket.id}`,
        {
          method: "DELETE",
          headers: {
            "X-WP-Nonce": WPIT_Admin.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.notyf.success(this.translations.ticket_deleted);
            this.closeTicketDetail();
            this.fetchTickets();
          } else {
            this.notyf.error(`Erreur : ${data.message}`);
            console.error("Erreur lors de la suppression :", data.message);
          }
        })
        .catch((error) => {
          this.notyf.error(`Erreur : ${error}`);
          console.error("Erreur lors de la suppression :", error);
        })
        .finally(() => {
          this.closeDeleteConfirmationModal();
        });
    },
  },
};
</script>
