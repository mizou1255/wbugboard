<template>
  <div class="pt-2 pr-4">
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>

    <Card topMargin="mt-8">
      <div class="flex justify-between items-center">
        <h2 class="card-title">{{ translations.settings }}</h2>
      </div>
      <div class="divider mt-2"></div>

      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
      >
        <!-- Tabs (1/3 width) -->
        <div class="tabs tabs-vertical tabs-boxed col-span-1">
          <a
            :class="tabClass(1)"
            @click="selectTab(1)"
            class="justify-start w-full"
          >
            <i class="fas fa-cog mr-2"></i> {{ translations.settings_general }}
          </a>
          <a
            :class="tabClass(2)"
            @click="selectTab(2)"
            class="justify-start w-full"
          >
            <i class="fas fa-envelope-open-text mr-2"></i>
            {{ translations.settings_email }}
          </a>
          <a
            v-if="emailSettings.adminNotifications"
            :class="tabClass(5)"
            @click="selectTab(5)"
            class="justify-start w-full"
          >
            <i class="fas fa-envelope-open mr-2"></i>
            {{ translations.settings_email_admin }}
          </a>
          <a
            v-if="emailSettings.userNotifications"
            :class="tabClass(6)"
            @click="selectTab(6)"
            class="justify-start w-full"
          >
            <i class="fas fa-envelope mr-2"></i>
            {{ translations.settings_email_user }}
          </a>

          <a
            :class="tabClass(3)"
            @click="selectTab(3)"
            class="justify-start w-full"
          >
            <i class="fas fa-tasks mr-2"></i>
            {{ translations.settings_priorities }}
          </a>
          <a
            :class="tabClass(4)"
            @click="selectTab(4)"
            class="justify-start w-full"
          >
            <i class="fas fa-user-shield mr-2"></i>
            {{ translations.user_roles }}
          </a>
        </div>

        <!-- Content Tabs (3/4 width) -->
        <div
          class="col-span-3 p-4 bg-base-300 rounded-lg shadow-md content-tabs"
        >
          <!-- Loading Spinner -->
          <div
            v-if="loading"
            class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50"
          >
            <span
              class="loading loading-spinner text-primary loading-lg"
            ></span>
          </div>

          <!-- Paramètres Généraux Tab -->
          <div v-if="selectedTab === 1">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.general_settings }}
            </h2>

            <div class="wpit-group mb-4">
              <label>{{ translations.activate_plugin_front }}</label>
              <input
                type="checkbox"
                v-model="generalSettings.activatePlugin"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="wpit-group mb-4">
              <label>{{ translations.client_change_status }}</label>
              <input
                type="checkbox"
                v-model="generalSettings.clientChangeStatus"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="wpit-group mb-4">
              <label>{{ translations.client_delete_ticket }}</label>
              <input
                type="checkbox"
                v-model="generalSettings.clientDeleteTicket"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="wpit-group mb-4">
              <label>{{ translations.activate_maintenance_mode }}</label>
              <input
                type="checkbox"
                v-model="generalSettings.maintenanceMode"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="wpit-group form-control mb-4">
              <label class="wpit-label label">{{
                translations.default_ticket_status
              }}</label>
              <select
                v-model="generalSettings.defaultStatus"
                class="wpit-input input input-bordered"
              >
                <option value="new">{{ translations.new }}</option>
                <option value="waiting">{{ translations.waiting }}</option>
                <option value="in_progress">
                  {{ translations.in_progress }}
                </option>
                <option value="resolved">{{ translations.resolved }}</option>
                <option value="closed">{{ translations.closed }}</option>
              </select>
            </div>
            <div class="flex justify-end mt-4">
              <button
                @click="saveGeneralSettings"
                class="btn btn-primary rounded-full"
              >
                <i class="fas fa-save"></i>
                {{ translations.save }}
              </button>
            </div>
          </div>

          <!-- Personnalisation des Emails Tab -->
          <div v-if="selectedTab === 2">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.email_customization }}
            </h2>

            <div class="wpit-group mb-4">
              <label>{{ translations.enable_admin_notifications }}</label>
              <input
                type="checkbox"
                v-model="emailSettings.adminNotifications"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="wpit-group mb-4">
              <label>{{ translations.enable_user_notifications }}</label>
              <input
                type="checkbox"
                v-model="emailSettings.userNotifications"
                class="wcpa-ui-toggle"
              />
            </div>

            <div class="flex justify-end mt-4">
              <button
                @click="saveEmailSettings"
                class="btn btn-primary rounded-full"
              >
                <i class="fas fa-save"></i>
                {{ translations.save }}
              </button>
            </div>
          </div>

          <!-- Email Admin Tab -->
          <div v-if="selectedTab === 5 && emailSettings.adminNotifications">
            <h3 class="text-lg font-semibold mb-4">
              {{ translations.admin_email }}
            </h3>
            <div class="wpit-group mb-4">
              <label>{{ translations.different_admin_email }}</label>
              <input
                type="checkbox"
                v-model="emailSettings.useCustomEmail"
                class="wcpa-ui-toggle"
              />
            </div>

            <div
              v-if="emailSettings.useCustomEmail"
              class="wpit-group form-control mb-4"
            >
              <label class="wpit-label label">{{
                translations.notification_email
              }}</label>
              <input
                type="email"
                v-model="emailSettings.notificationEmail"
                class="wpit-input input input-bordered"
              />
            </div>
            <div class="wpit-group form-control mb-4">
              <label class="wpit-label label">{{
                translations.email_subject
              }}</label>
              <input
                type="text"
                v-model="emailSettings.adminSubject"
                class="wpit-input input input-bordered"
              />
            </div>
            <div class="form-control mb-4">
              <label class="wpit-label label">{{
                translations.email_message
              }}</label>
              <div>
                <vue-editor
                  v-model="emailSettings.adminBody"
                  :editorToolbar="toolbarOptions"
                ></vue-editor>
              </div>
            </div>

            <div class="flex justify-end mt-4">
              <button
                @click="saveEmailSettings"
                class="btn btn-primary rounded-full"
              >
                <i class="fas fa-save"></i>
                {{ translations.save }}
              </button>
            </div>
          </div>

          <!-- Email User Tab -->
          <div v-if="selectedTab === 6 && emailSettings.userNotifications">
            <h3 class="text-lg font-semibold mb-4">
              {{ translations.user_email }}
            </h3>
            <div class="wpit-group form-control mb-4">
              <label class="wpit-label label">{{
                translations.email_subject
              }}</label>
              <input
                type="text"
                v-model="emailSettings.userSubject"
                class="wpit-input input input-bordered"
              />
            </div>
            <div class="form-control mb-4">
              <label class="wpit-label label">{{
                translations.email_message
              }}</label>
              <div>
                <vue-editor
                  v-model="emailSettings.userBody"
                  :editorToolbar="toolbarOptions"
                ></vue-editor>
              </div>
            </div>

            <div class="flex justify-end mt-4">
              <button
                @click="saveEmailSettings"
                class="btn btn-primary rounded-full"
              >
                <i class="fas fa-save"></i>
                {{ translations.save }}
              </button>
            </div>
          </div>

          <!-- Priorities Tab -->
          <div v-if="selectedTab === 3">
            <h2 class="text-xl font-semibold mb-4">
              {{ translations.priorities }}
            </h2>
            <dialog
              v-if="showPrioritesModal"
              id="modal_priorities"
              class="modal"
            >
              <div class="modal-box">
                <h3 class="text-xl font-semibold mb-4">
                  {{
                    editingPriority
                      ? translations.edit_priority
                      : translations.add_priority
                  }}
                </h3>
                <form @submit.prevent="savePriority">
                  <button
                    class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
                    @click="closePriorityModal"
                  >
                    ✕
                  </button>
                  <div class="flex wpit-group form-group mb-4">
                    <label class="wpit-label label" for="name_priority">{{
                      translations.priority_name
                    }}</label>
                    <input
                      type="text"
                      id="name_priority"
                      v-model="priorityForm.name"
                      class="wpit-input input input-bordered w-full"
                      required
                    />
                  </div>
                  <div class="form-group mt-4 flex justify-between">
                    <button
                      type="button"
                      class="btn btn-secondary rounded-full"
                      @click="closePriorityModal"
                    >
                      {{ translations.cancel }}
                    </button>
                    <button type="submit" class="btn btn-primary rounded-full">
                      <i class="fas fa-save"></i>
                      {{
                        editingPriority ? translations.save : translations.add
                      }}
                    </button>
                  </div>
                </form>
              </div>
            </dialog>

            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ translations.priority_methods }}
                </h3>
                <button
                  class="btn btn-primary rounded-full"
                  @click="addPriority"
                >
                  <i class="fas fa-plus-circle mr-2"></i
                  >{{ translations.add_method }}
                </button>
              </div>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.id }}</th>
                      <th>{{ translations.name }}</th>
                      <th>{{ translations.actions }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="priority in priorities" :key="priority.id">
                      <td>{{ priority.id }}</td>
                      <td>{{ priority.name }}</td>
                      <td>
                        <button
                          class="p-2 text-secondary"
                          @click="editPriority(priority.id)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          class="p-2 text-error"
                          @click="deletePriority(priority.id)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- User Roles Tab -->
          <div v-if="selectedTab === 4">
            <!-- Gestion des rôles -->
            <div class="mb-8">
              <h3 class="text-lg font-semibold mb-4">
                {{ translations.user_settings }}
              </h3>
              <h4 class="text-lg font-semibold mb-4">
                {{ translations.user_roles }}
              </h4>
              <div class="table-container">
                <table class="table w-full">
                  <thead>
                    <tr>
                      <th>{{ translations.role }}</th>
                      <th>{{ translations.access }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="role in roles" :key="role.name">
                      <td>{{ role.label }}</td>
                      <td>
                        <input
                          type="checkbox"
                          class="wcpa-ui-toggle"
                          :checked="role.hasAccess"
                          @change="toggleRoleAccess(role.name)"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Gestion des utilisateurs de l'équipe -->
            <div>
              <h4 class="text-lg font-semibold mb-4">
                {{ translations.select_team_users }}
              </h4>
              <div>
                <multiselect
                  v-model="selectedUsers"
                  :options="allUsers"
                  :multiple="true"
                  track-by="id"
                  label="name"
                  :placeholder="translations.select_team_users"
                  class="w-full"
                >
                </multiselect>
              </div>
            </div>

            <div class="flex justify-end mt-4">
              <button
                @click="saveUsersSettings"
                class="btn btn-primary rounded-full"
              >
                <i class="fas fa-save"></i>
                {{ translations.save }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>


<script>
import Card from "@/components/Card.vue";
import { VueEditor } from "vue3-editor";
import Multiselect from "vue-multiselect";
export default {
  name: "Settings",
  components: { Card, VueEditor, Multiselect },
  data() {
    return {
      loading: false,
      showPrioritesModal: false,
      priorities: [],
      editingPriority: false,
      selectedTab: 1,
      priorityForm: { id: null, name: "" },
      generalSettings: {
        activatePlugin: true,
        defaultStatus: "",
        clientChangeStatus: false,
        clientDeleteTicket: false,
        maintenanceMode: false,
      },
      emailSettings: {
        adminNotifications: true,
        userNotifications: true,
        useCustomEmail: false,
        notificationEmail: "",
        adminSubject: "Notification pour Administrateur",
        adminBody: "Détail du ticket pour l'administrateur.",
        userSubject: "Notification pour Utilisateur",
        userBody: "Détail du ticket pour l'utilisateur.",
      },
      roles: [],
      selectedUsers: [], // Utilisateurs sélectionnés (format d'objet)
      allUsers: [], // Liste des utilisateurs disponibles
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
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
  computed: {
    translations() {
      return window.WPIT_Admin.WPIT_trans;
    },
  },
  beforeUnmount() {
    window.removeEventListener("hashchange", this.checkHash);
  },
  mounted() {
    this.checkHash();
    window.addEventListener("hashchange", this.checkHash);
    this.fetchSettings();
    this.fetchPriorities();
    this.fetchUserRoles();
    this.fetchAllUsers();
    this.fetchTeamUsers();
  },
  methods: {
    selectTab(tab) {
      this.selectedTab = tab;
      window.location.hash = `tab${tab}`;
    },
    tabClass(tab) {
      return this.selectedTab === tab ? "tab tab-active" : "tab";
    },
    checkHash() {
      const hash = window.location.hash;
      if (hash) {
        const tab = parseInt(hash.replace("#tab", ""));
        if (!isNaN(tab)) {
          this.selectedTab = tab;
        }
      }
    },
    async fetchSettings() {
      this.loading = true;
      try {
        const response = await fetch("/wp-json/wbugboard/v1/settings", {
          method: "GET",
          headers: {
            "X-WP-Nonce": WPIT_Admin.nonce,
          },
        });

        if (!response.ok) {
          throw new Error("Erreur lors de la récupération des paramètres.");
        }

        const data = await response.json();

        // Mise à jour des settings avec les données reçues
        this.generalSettings = data.settings.general || this.generalSettings;
        this.emailSettings = data.settings.email || this.emailSettings;

        this.showToast("Paramètres chargés avec succès", "alert-success");
      } catch (error) {
        console.error("Erreur lors de la récupération des paramètres:", error);
        this.showToast(
          "Erreur lors de la récupération des paramètres",
          "alert-error"
        );
      } finally {
        this.loading = false;
      }
    },
    // Fetch Priorities
    fetchPriorities() {
      fetch("/wp-json/wbugboard/v1/priorities", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.priorities = data;
        })
        .catch((error) =>
          console.error("Erreur lors de la récupération des priorités:", error)
        );
    },
    addPriority() {
      this.priorityForm = {
        id: null,
        name: "",
      };
      this.editingPriority = false;
      this.showPrioritesModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_priorities").showModal();
      });
    },
    // Edit existing priority
    editPriority(id) {
      const priority = this.priorities.find((priority) => priority.id === id);
      this.priorityForm = { ...priority };
      this.editingPriority = true;
      this.showPrioritesModal = true;
      this.$nextTick(() => {
        document.getElementById("modal_priorities").showModal();
      });
    },
    // Delete priority
    async deletePriority(id) {
      try {
        const response = await fetch(
          `/wp-json/wbugboard/v1/priorities/${id}`,
          {
            method: "DELETE",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": WPIT_Admin.nonce,
            },
          }
        );

        if (response.ok) {
          this.priorities = this.priorities.filter(
            (priority) => priority.id !== id
          );
          this.showToast("Priorité supprimée avec succès", "alert-success");
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    // Save priority (add or edit)
    async savePriority() {
      const method = this.editingPriority ? "PUT" : "POST";
      const url = this.editingPriority
        ? `/wp-json/wbugboard/v1/priorities/${this.priorityForm.id}`
        : "/wp-json/wbugboard/v1/priorities";

      try {
        const response = await fetch(url, {
          method,
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": WPIT_Admin.nonce,
          },
          body: JSON.stringify(this.priorityForm),
        });

        if (response.ok) {
          const result = await response.json();
          if (this.editingPriority) {
            const index = this.priorities.findIndex(
              (priority) => priority.id === result.id
            );
            this.priorities.splice(index, 1, result); // Update in place
          } else {
            this.priorities.push(result); // Add new priority
          }
          this.showToast(
            `Priorité ${
              this.editingPriority ? "modifiée" : "ajoutée"
            } avec succès`,
            "alert-success"
          );
          this.closePriorityModal();
        } else {
          const error = await response.json();
          this.showToast(error.message, "alert-error");
        }
      } catch (error) {
        this.showToast(error.message, "alert-error");
      }
    },
    // Close modal
    closePriorityModal() {
      this.showPrioritesModal = false;
    },
    fetchUserRoles() {
      this.loading = true;
      fetch("/wp-json/wbugboard/v1/roles-access", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Admin.nonce, // Utilisation du nonce pour sécuriser la requête
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.roles = data.roles; // Mettre à jour la liste des rôles
          this.loading = false;
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des rôles:", error);
          this.loading = false;
        });
    },

    // Permet de changer l'état d'accès d'un rôle
    toggleRoleAccess(roleName) {
      const role = this.roles.find((r) => r.name === roleName);
      role.hasAccess = !role.hasAccess;
    },

    // Sauvegarder les rôles avec accès
    saveUsersSettings() {
      const rolesWithAccess = this.roles
        .filter((role) => role.hasAccess)
        .map((role) => role.name);

      fetch("/wp-json/wbugboard/v1/update-users-settings", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": WPIT_Admin.nonce, // Utilisation du nonce côté client
        },
        body: JSON.stringify({
          roles: rolesWithAccess,
          users: this.selectedUsers.map((user) => user.id),
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.showToast(
              "Les paramètres ont été sauvegardés avec succès",
              "alert-success"
            );
          } else {
            this.showToast("Erreur lors de la sauvegarde", "alert-error");
          }
        })
        .catch((error) => {
          console.error("Erreur lors de la sauvegarde des paramètres:", error);
          this.showToast("Erreur lors de la sauvegarde", "alert-error");
        });
    },

    // Save General Settings
    async saveGeneralSettings() {
      try {
        const response = await fetch(
          "/wp-json/wbugboard/v1/settings/general",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": WPIT_Admin.nonce,
            },
            body: JSON.stringify(this.generalSettings),
          }
        );

        const data = await response.json();
        if (response.ok) {
          this.showToast(
            "Paramètres généraux enregistrés avec succès",
            "alert-success"
          );
        } else {
          this.showToast(
            data.message || "Erreur lors de l'enregistrement des paramètres",
            "alert-error"
          );
        }
      } catch (error) {
        this.showToast(
          "Erreur lors de l'enregistrement des paramètres",
          "alert-error"
        );
      }
    },

    // Save Email Settings
    async saveEmailSettings() {
      try {
        const response = await fetch(
          "/wp-json/wbugboard/v1/settings/email",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": WPIT_Admin.nonce,
            },
            body: JSON.stringify(this.emailSettings),
          }
        );

        const data = await response.json();
        if (response.ok) {
          this.showToast(
            "Paramètres d'email enregistrés avec succès",
            "alert-success"
          );
        } else {
          this.showToast(
            data.message || "Erreur lors de l'enregistrement des paramètres",
            "alert-error"
          );
        }
      } catch (error) {
        this.showToast(
          "Erreur lors de l'enregistrement des paramètres",
          "alert-error"
        );
      }
    },
    async fetchAllUsers() {
      try {
        const response = await fetch("/wp-json/wbugboard/v1/all-users", {
          method: "GET",
          headers: { "X-WP-Nonce": WPIT_Admin.nonce },
        });
        if (!response.ok) {
          throw new Error("Erreur lors de la récupération des utilisateurs.");
        }
        const users = await response.json();
        this.allUsers = users.map((user) => ({
          id: user.value,
          name: user.text,
        }));
      } catch (error) {
        console.error(
          "Erreur lors de la récupération des utilisateurs :",
          error
        );
      }
    },

    // Charger les utilisateurs sélectionnés
    async fetchTeamUsers() {
      try {
        const response = await fetch("/wp-json/wbugboard/v1/team-users", {
          method: "GET",
          headers: { "X-WP-Nonce": WPIT_Admin.nonce },
        });
        if (!response.ok) {
          throw new Error(
            "Erreur lors de la récupération des utilisateurs sélectionnés."
          );
        }
        const data = await response.json();
        this.selectedUsers = data.users.map((user) => ({
          id: user.id,
          name: user.name,
        }));
      } catch (error) {
        console.error(
          "Erreur lors de la récupération des utilisateurs sélectionnés :",
          error
        );
      }
    },

    // Show toast notification
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000); // Hide toast after 3 seconds
    },
  },
};
</script>
  