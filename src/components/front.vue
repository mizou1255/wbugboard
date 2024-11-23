<template>
  <div v-if="settings.userHasAccess && settings.activatePlugin">
    <div id="floating-ticket-button">
      <div @click="captureScreen">
        <div v-if="loading" class="wpit-spinner"></div>
        <i v-else class="fas fa-camera"></i>
      </div>
      <div @click="fetchUserTickets">
        <div v-if="loadingList" class="wpit-spinner"></div>
        <i v-else class="far fa-list-alt"></i>
      </div>
    </div>

    <div v-if="showTicketsModal" class="modal-overlay">
      <div class="modal-content ticket-modal-content">
        <span class="close-button" @click="closeTicketsModal">&times;</span>
        <h2 class="modal-title">{{ translations.my_tickets }}</h2>

        <div class="ticket-layout">
          <ul class="ticket-list">
            <li
              v-for="(ticket, index) in userTickets"
              :key="ticket.id"
              :class="{ active: index === activeTicketIndex }"
              @click="setActiveTicket(index)"
              class="ticket-item"
            >
              <span>{{ ticket.title }}</span>
              <span class="status-label" :class="getStatusClass(ticket.status)">
                {{ getStatusLabel(ticket.status) }}
              </span>
            </li>
          </ul>

          <div class="ticket-details" v-if="activeTicket">
            <div class="ticket-details-info">
              <div>
                <h3>{{ activeTicket.title }}</h3>
                <div v-html="activeTicket.description"></div>
                <p v-if="activeTicket.assigned_user_name">
                  <strong>{{ translations.assigned_to }} :</strong>
                  {{ activeTicket.assigned_user_name }}
                </p>
                <p>
                  <strong>{{ translations.priority }} :</strong>
                  {{ activeTicket.priority }}
                </p>
                <p>
                  <strong>{{ translations.status }} :</strong>
                  {{ selectedStatusLabel }}
                </p>
              </div>
              <div>
                <!-- Bouton rouge pour supprimer -->
                <div
                  v-if="settings.clientDeleteTicket"
                  class="delete-ticket-btn mb-4"
                >
                  <button
                    @click="showDeleteConfirmationModal"
                    class="wpit-btn btn-danger"
                  >
                    {{ translations.delete_ticket }}
                  </button>
                </div>
                <a
                  v-if="activeTicket.attachment_url"
                  :href="activeTicket.attachment_url"
                  target="_blank"
                  ><img :src="activeTicket.attachment_url" width="200"
                /></a>
              </div>
            </div>

            <div v-if="settings.clientChangeStatus">
              <hr />
              <div class="wpit-group form-control mb-4">
                <label class="wpit-label label" for="ticketStatus">{{
                  translations.change_status
                }}</label>
                <select
                  autocomplete="off"
                  v-model="activeTicket.status"
                  @change="
                    confirmUpdateStatus(activeTicket.id, activeTicket.status)
                  "
                  class="wpit-input input input-bordered"
                >
                  <option
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <hr />
            </div>

            <div class="add-comment">
              <div class="relative">
                <textarea
                  ref="commentInput"
                  v-model="newComment"
                  @input="handleInput"
                  @keydown="handleKeyDown"
                  placeholder="Tapez votre commentaire ici..."
                  class="comment-input"
                ></textarea>
                <!-- Liste des suggestions -->
                <ul
                  v-if="showSuggestions"
                  class="mention-suggestions"
                  :style="{
                    top: suggestionPosition.top,
                    left: suggestionPosition.left,
                  }"
                >
                  <li
                    v-for="(user, index) in filteredUsers"
                    :key="user.id"
                    :class="{ 'is-active': index === activeSuggestionIndex }"
                    @mousedown.prevent="selectUser(user)"
                  >
                    {{ user.name }}
                  </li>
                </ul>
              </div>
              <button
                class="wpit-btn btn-primary"
                @click="submitComment"
                :disabled="!newComment"
              >
                {{ translations.send }}
              </button>
            </div>

            <div class="comments-section">
              <h4>{{ translations.comments }}</h4>
              <div v-if="loadingComments">
                <div class="skeleton-loader skeleton-h-2 skeleton-w-50"></div>
                <div class="skeleton-loader skeleton-h-2 skeleton-w-75"></div>
                <div class="skeleton-loader skeleton-h-2 skeleton-w-100"></div>
              </div>
              <ul v-else class="comment-list">
                <li
                  v-for="comment in comments"
                  :key="comment.id"
                  class="comment-item"
                >
                  <strong>{{ comment.author }}:</strong> {{ comment.comment }}
                  <small>{{ comment.date }} </small>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

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
          <button
            @click="closeConfirmationModal"
            class="wpit-btn btn-secondary"
          >
            {{ translations.cancel }}
          </button>
          <button @click="confirmStatusChange" class="wpit-btn btn-primary">
            {{ translations.confirm }}
          </button>
        </div>
      </div>
    </dialog>

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
            class="wpit-btn btn-secondary"
          >
            {{ translations.cancel }}
          </button>
          <button @click="confirmDeleteTicket" class="wpit-btn btn-danger">
            {{ translations.confirm_delete }}
          </button>
        </div>
      </div>
    </dialog>

    <div v-if="showModal" class="modal-overlay">
      <div v-if="showCropActions" class="crop-actions">
        <button class="wpit-btn btn-primary" @click="applyCrop">
          ✔️ {{ translations.confirm }}
        </button>
        <button class="wpit-btn btn-secondary" @click="cancelCrop">
          ❌ {{ translations.cancel }}
        </button>
      </div>
      <div class="modal-content">
        <span class="close-button" @click="closeModal">&times;</span>
        <h2 class="modal-title">{{ translations.screenshot_preview }}</h2>
        <div class="toolbar">
          <div>
            <button
              :class="{ 'toolbar-btn': true, active: tool === 'select' }"
              @click="setTool('select')"
              title="Sélectionner"
            >
              <i class="fas fa-mouse-pointer"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'crop' }"
              @click="setTool('crop')"
              title="Crop"
            >
              <i class="fas fa-crop"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'pencil' }"
              @click="setTool('pencil')"
              title="Crayon"
            >
              <i class="fas fa-pencil-alt"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'rectangle' }"
              @click="setTool('rectangle')"
              title="Rectangle"
            >
              <i class="far fa-square"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'circle' }"
              @click="setTool('circle')"
              title="Cercle"
            >
              <i class="far fa-circle"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'arrow' }"
              @click="setTool('arrow')"
              title="Flèche"
            >
              <i class="fas fa-long-arrow-alt-right"></i>
            </button>

            <button
              :class="{ 'toolbar-btn': true, active: tool === 'comment' }"
              @click="setTool('comment')"
              title="Commentaire"
            >
              <i class="fas fa-comment-alt"></i>
            </button>
          </div>

          <div>
            <input type="color" v-model="strokeColor" class="toolbar-input" />

            <input
              type="range"
              v-model="strokeWidth"
              min="1"
              max="50"
              class="toolbar-input"
            />
          </div>

          <div>
            <button
              class="toolbar-btn"
              @click="undo"
              :title="translations.cancel"
            >
              <i class="fas fa-undo"></i>
            </button>

            <button
              class="toolbar-btn"
              @click="redo"
              :title="translations.undo"
            >
              <i class="fas fa-redo"></i>
            </button>

            <button
              class="toolbar-btn-send send-ticket"
              @click="showTicketForm"
              :title="translations.send"
            >
              <i class="fas fa-paper-plane"></i>
              {{ translations.send }}
            </button>
          </div>
        </div>

        <div id="preview">
          <div id="konvaCanvas"></div>
        </div>

        <div v-if="showTicketModal" class="modal-overlay">
          <div class="modal-content ticket-send">
            <span class="close-button" @click="closeTicketModal">&times;</span>
            <h2 class="modal-title">{{ translations.submit_ticket }}</h2>
            <div class="ticket-form">
              <div
                :class="['wpit-group', { 'input-error': errors.title }]"
                class="form-control mb-4"
              >
                <label class="wpit-label label" for="ticketTitle">{{
                  translations.title
                }}</label>
                <input
                  type="text"
                  id="ticketTitle"
                  class="wpit-input input input-bordered"
                  v-model="ticketTitle"
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
                    v-model="ticketDescription"
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
                  v-model="ticketAsigned"
                  class="wpit-input input input-bordered"
                >
                  <option value="">{{ translations.unassigned }}</option>
                  <option
                    v-for="user in teamUsers"
                    :key="user.id"
                    :value="user.id"
                  >
                    {{ user.name }}
                  </option>
                </select>
              </div>

              <div
                :class="['wpit-group', { 'input-error': errors.priority }]"
                class="form-control mb-4"
              >
                <label class="wpit-label label" for="ticketPriority">{{
                  translations.priority
                }}</label>
                <select
                  id="ticketPriority"
                  class="wpit-input input input-bordered"
                  v-model="ticketPriority"
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

              <button
                class="wpit-btn btn-primary"
                @click="submitTicketWithScreenshot"
                :disabled="loadingSubmit"
              >
                {{ translations.send_ticket }}
                <span
                  v-if="loadingSubmit"
                  class="inline-block vertical-middle wpit-spinner"
                ></span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
  
<script>
import html2canvas from "html2canvas";
import { nextTick } from "vue";
import { Notyf } from "notyf";
import { VueEditor } from "vue3-editor";

export default {
  name: "Front",
  components: { VueEditor },
  data() {
    const translations = window.WPIT_Front.WPIT_trans;
    return {
      loading: false,
      loadingList: false,
      loadingComments: false,
      loadingSubmit: false,
      showModal: false,
      showTicketModal: false,
      showTicketsModal: false,
      teamUsers: [],
      userTickets: [],
      activeTicket: "",
      activeTicketIndex: "",
      comments: [],
      newComment: "",
      showSuggestions: false,
      filteredUsers: [],
      activeSuggestionIndex: 0,
      suggestionPosition: { top: "0px", left: "0px" },
      priorities: [],
      ticketPriority: "",
      ticketTitle: "",
      ticketDescription: "",
      ticketAsigned: "",
      stage: null,
      layer: null,
      transformer: null,
      strokeColor: "#FF0000",
      strokeWidth: 5,
      tool: null,
      cropRect: null, // Le rectangle de recadrage
      cropTransformer: null, // Le transformer pour le rectangle
      showCropActions: false, // Afficher les boutons Valider/Annuler
      originalImageNode: null, // Référence à l'image d'origine
      shapesHistory: [],
      redoHistory: [],
      screenshotData: null,
      isDrawing: false,
      currentShape: null,
      imageNode: null,
      settings: [],
      showConfirmationModal: false,
      originalStatus: "",
      ticketIdToUpdate: null,
      showDeleteModal: false,
      statusToConfirm: "",
      statusOptions: [
        { value: "new", label: translations.new },
        { value: "in_progress", label: translations.in_progress },
        { value: "waiting", label: translations.waiting },
        { value: "resolved", label: translations.resolved },
        { value: "closed", label: translations.closed },
      ],
      errors: {
        title: false,
        content: false,
        priority: false,
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
    };
  },
  mounted() {
    this.fetchSettings();
    this.fetchPriorities();
    this.fetchTeamUsers();
    this.initializeNotyf();
  },
  computed: {
    translations() {
      return window.WPIT_Front.WPIT_trans;
    },
    selectedStatusLabel() {
      const selectedOption = this.statusOptions.find(
        (option) => option.value === this.activeTicket.status
      );
      return selectedOption ? selectedOption.label : "";
    },
  },
  methods: {
    initializeNotyf() {
      this.notyf = new Notyf({
        duration: 3000,
        position: { x: "right", y: "bottom" },
      });
    },
    captureScreen() {
      this.loading = true;
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      const scrollX = window.scrollX || window.pageXOffset;
      const scrollY = window.scrollY || window.pageYOffset;

      html2canvas(document.body, {
        width: viewportWidth,
        height: viewportHeight,
        x: scrollX,
        y: scrollY,
        scrollX: 0,
        scrollY: 0,
      })
        .then((canvas) => {
          this.screenshotData = canvas.toDataURL("image/png");
          this.showModal = true;
          this.loading = false;

          nextTick(() => {
            this.initializeKonvaCanvas();
          });
        })
        .catch((error) => {
          this.notyf.error("Erreur lors de la capture d'écran", error);
          console.error("Erreur lors de la capture d'écran", error);
          this.loading = false;
        });
    },

    initializeKonvaCanvas() {
      if (!this.screenshotData) {
        console.error("Capture d'écran introuvable");
        this.notyf.error("Capture d'écran introuvable");
        return;
      }

      const imageObj = new Image();
      imageObj.src = this.screenshotData;

      imageObj.onload = () => {
        const imageWidth = imageObj.width;
        const imageHeight = imageObj.height;
        const parentWidth = document.getElementById("konvaCanvas").offsetWidth;

        const aspectRatio = imageWidth / imageHeight;
        const canvasWidth = parentWidth;
        const canvasHeight = canvasWidth / aspectRatio;

        this.stage = new Konva.Stage({
          container: "konvaCanvas",
          width: canvasWidth,
          height: canvasHeight,
        });

        this.layer = new Konva.Layer();
        this.stage.add(this.layer);

        this.originalImageNode = new Konva.Image({
          x: 0,
          y: 0,
          image: imageObj,
          width: canvasWidth,
          height: canvasHeight,
        });

        // Stocker les dimensions réelles de l'image
        this.imageRealWidth = imageWidth;
        this.imageRealHeight = imageHeight;

        this.layer.add(this.originalImageNode);
        this.layer.draw();

        this.stage.on("mousedown", (e) => {
          if (this.tool === "crop") {
            this.startCrop(e);
          } else {
            this.startDrawing(e);
          }
        });

        this.stage.on("mousemove", (e) => {
          if (this.tool === "crop") {
            this.cropDrawing(e);
          } else {
            this.drawing(e);
          }
        });

        this.stage.on("mouseup", (e) => {
          if (this.tool === "crop") {
            this.endCrop();
          } else {
            this.endDrawing(e);
          }
        });
      };
    },

    setTool(tool) {
      if (this.tool === "crop" && this.cropRect) {
        this.cancelCrop(); // Annule les actions de crop si l'utilisateur change d'outil
      }

      this.tool = tool;

      // Gérer l'affichage des actions liées au recadrage
      this.showCropActions = tool === "crop";
    },

    startDrawing(e) {
      if (!this.tool || this.tool === "select") return;
      const pos = this.stage.getPointerPosition();

      if (this.tool === "comment") {
        this.addComment(pos.x, pos.y);
        this.setTool("select");
        return;
      }

      if (this.tool === "pencil") {
        this.currentShape = new Konva.Line({
          stroke: this.strokeColor,
          strokeWidth: this.strokeWidth,
          points: [pos.x, pos.y],
          lineCap: "round",
          lineJoin: "round",
        });
      } else if (this.tool === "rectangle") {
        this.currentShape = new Konva.Rect({
          x: pos.x,
          y: pos.y,
          width: 0,
          height: 0,
          stroke: this.strokeColor,
          strokeWidth: this.strokeWidth,
          draggable: true,
        });
      } else if (this.tool === "circle") {
        this.currentShape = new Konva.Circle({
          x: pos.x,
          y: pos.y,
          radius: 0,
          stroke: this.strokeColor,
          strokeWidth: this.strokeWidth,
          draggable: true,
        });
      } else if (this.tool === "arrow") {
        this.currentShape = new Konva.Arrow({
          points: [pos.x, pos.y, pos.x, pos.y],
          stroke: this.strokeColor,
          strokeWidth: this.strokeWidth,
          pointerLength: 10,
          pointerWidth: 10,
          draggable: true,
        });
      }

      this.layer.add(this.currentShape);
      this.isDrawing = true;
    },

    drawing(e) {
      if (!this.isDrawing || !this.currentShape) return;

      const pos = this.stage.getPointerPosition();
      if (this.tool === "pencil") {
        const newPoints = this.currentShape.points().concat([pos.x, pos.y]);
        this.currentShape.points(newPoints);
      } else if (this.tool === "rectangle") {
        const newWidth = pos.x - this.currentShape.x();
        const newHeight = pos.y - this.currentShape.y();
        this.currentShape.width(newWidth);
        this.currentShape.height(newHeight);
      } else if (this.tool === "circle") {
        const radius = Math.sqrt(
          Math.pow(pos.x - this.currentShape.x(), 2) +
            Math.pow(pos.y - this.currentShape.y(), 2)
        );
        this.currentShape.radius(radius);
      } else if (this.tool === "arrow") {
        this.currentShape.points([
          this.currentShape.points()[0],
          this.currentShape.points()[1],
          pos.x,
          pos.y,
        ]);
      }

      this.layer.batchDraw();
    },

    endDrawing() {
      this.isDrawing = false;
      if (this.currentShape) {
        this.shapesHistory.push(this.currentShape);
        this.currentShape = null;
      }
    },

    addComment(x, y) {
      const textNode = new Konva.Text({
        x: x,
        y: y,
        text: "Double-cliquez pour éditer",
        fontSize: 20,
        fill: this.strokeColor,
        draggable: true,
      });

      textNode.on("dblclick", () => {
        this.editTextNode(textNode);
      });

      this.layer.add(textNode);
      this.layer.draw();
      this.shapesHistory.push(textNode);
    },

    editTextNode(textNode) {
      const textarea = document.createElement("textarea");
      textarea.value = textNode.text();

      const textNodePosition = textNode.absolutePosition();
      const textHeight = textNode.height();
      const textWidth = textNode.width();

      textarea.style.position = "absolute";
      textarea.style.top = `${textNodePosition.y - 70}px`;
      textarea.style.left = `${textNodePosition.x}px`;
      textarea.style.fontSize = `${textNode.fontSize()}px`;
      textarea.style.width = `${textWidth}px`;

      textarea.style.padding = "5px";
      textarea.style.border = "1px solid black";
      textarea.style.background = "white";
      textarea.style.zIndex = 999999;

      document.querySelector("#preview").appendChild(textarea);
      textarea.focus();

      textarea.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          textNode.text(textarea.value);
          document.body.removeChild(textarea);
          this.layer.draw();
        }
      });

      textarea.addEventListener("blur", () => {
        textNode.text(textarea.value);
        document.querySelector("#preview").removeChild(textarea);
        this.layer.draw();
      });
    },

    undo() {
      if (this.shapesHistory.length === 0) return;
      const shape = this.shapesHistory.pop();
      this.redoHistory.push(shape);
      shape.remove();
      this.layer.batchDraw();
    },

    redo() {
      if (this.redoHistory.length === 0) return;
      const shape = this.redoHistory.pop();
      this.layer.add(shape);
      this.layer.batchDraw();
    },
    startCrop(e) {
      if (this.cropRect) {
        return; // Empêcher la création de plusieurs rectangles
      }

      const pos = this.stage.getPointerPosition();

      this.cropRect = new Konva.Rect({
        x: pos.x,
        y: pos.y,
        width: 0,
        height: 0,
        stroke: this.strokeColor,
        strokeWidth: this.strokeWidth,
        dash: [4, 4],
      });

      this.layer.add(this.cropRect);
      this.isDrawing = true;
    },

    cropDrawing(e) {
      if (!this.isDrawing || !this.cropRect) return;

      const pos = this.stage.getPointerPosition();
      this.cropRect.width(pos.x - this.cropRect.x());
      this.cropRect.height(pos.y - this.cropRect.y());
      this.layer.batchDraw();
    },

    endCrop() {
      this.isDrawing = false;

      if (this.cropRect) {
        // Ajouter un Transformer pour redimensionner le rectangle
        this.cropTransformer = new Konva.Transformer({
          nodes: [this.cropRect],
          rotateEnabled: false, // Désactiver la rotation
          keepRatio: false, // Permettre un redimensionnement libre
          enabledAnchors: [
            "top-left",
            "top-right",
            "bottom-left",
            "bottom-right",
          ],
        });

        this.layer.add(this.cropTransformer);
        this.cropTransformer.attachTo(this.cropRect);

        this.showCropActions = true; // Afficher les boutons
        this.layer.batchDraw();
      }
    },

    applyCrop() {
      if (!this.cropRect || !this.originalImageNode) return;

      const cropBox = this.cropRect.getClientRect();

      // Récupérer les dimensions du canvas
      const canvasWidth = this.originalImageNode.width();
      const canvasHeight = this.originalImageNode.height();

      // Convertir les coordonnées du cropBox à l'échelle réelle de l'image
      const scaleX = this.imageRealWidth / canvasWidth;
      const scaleY = this.imageRealHeight / canvasHeight;

      const realCropX = cropBox.x * scaleX;
      const realCropY = cropBox.y * scaleY;
      const realCropWidth = cropBox.width * scaleX;
      const realCropHeight = cropBox.height * scaleY;

      const croppedCanvas = document.createElement("canvas");
      croppedCanvas.width = realCropWidth;
      croppedCanvas.height = realCropHeight;

      const context = croppedCanvas.getContext("2d");
      const image = this.originalImageNode.image();

      context.drawImage(
        image,
        realCropX,
        realCropY,
        realCropWidth,
        realCropHeight,
        0,
        0,
        realCropWidth,
        realCropHeight
      );

      // Remplacer l'image d'origine par l'image recadrée
      this.originalImageNode.image(croppedCanvas);

      // Supprimer les zones de recadrage
      if (this.cropRect) {
        this.cropRect.destroy();
        this.cropRect = null;
      }

      if (this.cropTransformer) {
        this.cropTransformer.destroy();
        this.cropTransformer = null;
      }

      this.showCropActions = false;
      this.tool = null;

      this.layer.batchDraw();
    },

    cancelCrop() {
      if (this.cropRect) {
        this.cropRect.destroy();
        this.cropRect = null;
      }

      if (this.cropTransformer) {
        this.cropTransformer.destroy();
        this.cropTransformer = null;
      }

      this.showCropActions = false;
      this.tool = null;

      this.layer.batchDraw();
    },

    getBrowserInfo() {
      return JSON.stringify({
        url: window.location.href,
        title: document.title,
        userAgent: navigator.userAgent,
        browserLanguage: navigator.language,
        platform: navigator.platform,
        screenResolution: `${window.screen.width}x${window.screen.height}`,
        viewportSize: `${window.innerWidth}x${window.innerHeight}`,
        referrer: document.referrer,
      });
    },
    fetchUserTickets() {
      this.loadingList = true;

      fetch("/wp-json/wbugboard/v1/get-user-tickets", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.userTickets = data.tickets;
            this.showTicketsModal = true;
            //this.setActiveTicket(0);
          } else {
            this.notyf.error(
              `Erreur lors de la récupération des tickets: ${data.message}`
            );
            console.error(
              "Erreur lors de la récupération des tickets:",
              data.message
            );
          }
          this.loadingList = false;
        })
        .catch((error) => {
          this.notyf.error(
            `Erreur lors de la récupération des tickets: ${error}`
          );
          console.error("Erreur lors de la récupération des tickets:", error);
          this.loadingList = false;
        });
    },
    fetchSettings() {
      fetch("/wp-json/wbugboard/v1/user-settings", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.settings = data;
        })
        .catch((error) => {
          this.notyf.error(
            `Erreur lors de la récupération des settings: ${error}`
          );
          console.error("Erreur lors de la récupération des settings:", error);
        });
    },

    fetchPriorities() {
      fetch("/wp-json/wbugboard/v1/priorities")
        .then((response) => response.json())
        .then((data) => {
          this.priorities = data;
        })
        .catch((error) => {
          this.notyf.error(
            `Erreur lors de la récupération des priorités: ${error}`
          );
          console.error("Erreur lors de la récupération des priorités:", error);
        });
    },
    confirmUpdateStatus(ticketId, newStatus) {
      this.ticketIdToUpdate = ticketId;
      this.statusToConfirm = newStatus;
      document.getElementById("confirmation-modal").showModal();
    },
    confirmStatusChange() {
      this.updateTicketStatus(this.ticketIdToUpdate, this.statusToConfirm);
      this.closeConfirmationModal();
    },
    closeConfirmationModal() {
      document.getElementById("confirmation-modal").close();
      this.activeTicket.status = this.originalStatus;

      this.ticketIdToUpdate = null;
      this.statusToConfirm = "";
    },
    updateTicketStatus() {
      if (!this.activeTicket || !this.activeTicket.id) return;

      const formData = new FormData();
      formData.append("ticket_id", this.activeTicket.id);
      formData.append("status", this.activeTicket.status);

      fetch("/wp-json/wbugboard/v1/update-ticket-status", {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.notyf.success(this.translations.ticket_updated);
          } else {
            this.notyf.error(`Erreur: ${data.message}`);
            console.error(
              "Erreur lors de la mise à jour du statut:",
              data.message
            );
          }
        })
        .catch((error) => {
          this.notyf.error(`Erreur: ${error}`);
          console.error("Erreur lors de la mise à jour du statut:", error);
        });
    },
    validateFields() {
      // Initialiser les erreurs
      this.errors.title = !this.ticketTitle;
      this.errors.content = !this.ticketDescription;
      this.errors.priority = !this.ticketPriority;

      // Retourner true si tous les champs sont remplis, false sinon
      return (
        !this.errors.title && !this.errors.content && !this.errors.priority
      );
    },
    submitTicketWithScreenshot() {
      this.loadingSubmit = true;
      if (!this.validateFields()) {
        this.notyf.error(this.translations.validation_error);
        return;
      }

      const annotatedImage = this.stage.toDataURL({ pixelRatio: 2 });
      const formData = new FormData();
      formData.append(
        "file",
        this.dataURLtoBlob(annotatedImage),
        "screenshot.png"
      );
      formData.append("title", this.ticketTitle);
      formData.append("description", this.ticketDescription);
      formData.append("priority", this.ticketPriority);
      formData.append("asigned", this.ticketAsigned);

      // Ajouter les informations du navigateur en JSON
      const ticketInfo = this.getBrowserInfo();
      formData.append("ticket_info", ticketInfo);

      fetch("/wp-json/wbugboard/v1/front-create-ticket", {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.notyf.success(this.translations.ticket_created_success);
            this.errors = { title: false, content: false, priority: false };
            this.closeModal();
            this.closeTicketModal();

            this.ticketTitle = "";
            this.ticketDescription = "";
            this.ticketPriority = null;
            this.ticketAsigned = null;
            this.screenshotData = null;
            this.loadingSubmit = false;
            if (this.stage) {
              this.stage.destroy();
            }
          } else {
            this.notyf.error(`Erreur: ${data.error}`);
            this.loadingSubmit = false;
            console.error("Erreur lors de l'envoi du ticket:", data.message);
          }
        })
        .catch((error) => {
          this.notyf.error(`Erreur: ${error}`);
          this.loadingSubmit = false;
          console.error("Erreur lors de l'envoi:", error);
        });
    },

    fetchComments(ticketId) {
      this.loadingComments = true;
      fetch(`/wp-json/wbugboard/v1/get-ticket-comments/${ticketId}`, {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.comments = data.comments;
            this.loadingComments = false;
            this.originalStatus = this.activeTicket.status;
          } else {
            this.notyf.error(`Erreur: ${data.message}`);
            console.error(
              "Erreur lors de la récupération des commentaires:",
              data.message
            );
            this.loadingComments = false;
          }
        })
        .catch((error) => {
          this.notyf.error(`Erreur: ${error}`);
          console.error(
            "Erreur lors de la récupération des commentaires:",
            error
          );
        });
    },

    submitComment() {
      if (!this.newComment) return;

      const formData = new FormData();
      formData.append("ticket_id", this.activeTicket.id);
      formData.append("comment", this.newComment);

      fetch("/wp-json/wbugboard/v1/add-comment", {
        method: "POST",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
        },
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.comments.unshift(data.comment);
            this.newComment = "";
          } else {
            this.notyf.error(
              `Erreur lors de l'ajout du commentaire: ${data.message}`
            );
            console.error(
              "Erreur lors de l'ajout du commentaire:",
              data.message
            );
          }
        })
        .catch((error) => {
          this.notyf.error(`Erreur lors de l'ajout du commentaire: ${error}`);
          console.error("Erreur lors de l'ajout du commentaire:", error);
        });
    },

    fetchTeamUsers() {
      fetch("/wp-json/wbugboard/v1/team", {
        method: "GET",
        headers: {
          "X-WP-Nonce": WPIT_Front.nonce,
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
    handleInput(event) {
      const input = event.target.value;
      const cursorPosition = event.target.selectionStart;

      // Trouver la position du dernier `@` avant le curseur
      const textBeforeCursor = input.slice(0, cursorPosition);
      const mentionMatch = textBeforeCursor.match(/@(\w*)$/);

      if (mentionMatch) {
        const searchTerm = mentionMatch[1];

        // Filtrer les utilisateurs en fonction de la recherche
        this.filteredUsers = this.teamUsers.filter((user) =>
          user.name.toLowerCase().includes(searchTerm.toLowerCase())
        );

        if (this.filteredUsers.length > 0) {
          this.showSuggestions = true;

          // Obtenez la position exacte du curseur
          const textarea = this.$refs.commentInput;
          const { top, left } = this.getCursorCoordinates(
            textarea,
            cursorPosition
          );
          const topFinal = top + 20;

          // Positionnez la liste des suggestions
          this.suggestionPosition = {
            top: `${topFinal}px`,
            left: `${left}px`,
          };
        } else {
          this.showSuggestions = false;
        }
      } else {
        this.showSuggestions = false;
      }
    },
    getCursorCoordinates(textarea, cursorPosition) {
      // Créer un span temporaire pour simuler le texte
      const div = document.createElement("div");
      const style = window.getComputedStyle(textarea);

      // Copier le style du textarea dans le div
      Array.from(style).forEach((prop) => {
        div.style[prop] = style[prop];
      });

      div.style.position = "absolute";
      div.style.visibility = "hidden";
      div.style.whiteSpace = "pre-wrap";
      div.style.wordWrap = "break-word";
      div.style.overflow = "hidden";

      // Ajouter le contenu du textarea au div
      const textBeforeCursor = textarea.value.substring(0, cursorPosition);
      const textAfterCursor = textarea.value.substring(cursorPosition);

      div.textContent = textBeforeCursor;

      // Ajouter un span à l'emplacement du curseur
      const cursorSpan = document.createElement("span");
      cursorSpan.textContent = "|"; // Un marqueur temporaire
      div.appendChild(cursorSpan);

      // Ajouter le reste du texte après le curseur
      const afterText = document.createTextNode(textAfterCursor);
      div.appendChild(afterText);

      // Ajouter le div au DOM
      document.body.appendChild(div);

      // Obtenez la position du curseur
      const { top, left } = cursorSpan.getBoundingClientRect();

      // Nettoyer le DOM
      document.body.removeChild(div);

      return { top: top + window.scrollY, left: left + window.scrollX };
    },
    handleKeyDown(event) {
      if (this.showSuggestions) {
        if (event.key === "ArrowDown") {
          // Descendre dans la liste des suggestions
          event.preventDefault();
          this.activeSuggestionIndex =
            (this.activeSuggestionIndex + 1) % this.filteredUsers.length;
        } else if (event.key === "ArrowUp") {
          // Monter dans la liste des suggestions
          event.preventDefault();
          this.activeSuggestionIndex =
            (this.activeSuggestionIndex - 1 + this.filteredUsers.length) %
            this.filteredUsers.length;
        } else if (event.key === "Enter") {
          // Sélectionner l'utilisateur actif
          event.preventDefault();
          this.selectUser(this.filteredUsers[this.activeSuggestionIndex]);
        } else if (event.key === "Escape") {
          // Fermer les suggestions
          this.showSuggestions = false;
        }
      }
    },
    selectUser(user) {
      const cursorPosition = this.$refs.commentInput.selectionStart;

      // Remplacer le texte `@nom` par le nom de l'utilisateur
      const textBeforeCursor = this.newComment.slice(0, cursorPosition);
      const textAfterCursor = this.newComment.slice(cursorPosition);
      const mentionMatch = textBeforeCursor.match(/@(\w*)$/);

      if (mentionMatch) {
        const mentionStart = textBeforeCursor.lastIndexOf("@");
        this.newComment =
          textBeforeCursor.slice(0, mentionStart) +
          `@${user.name} ` +
          textAfterCursor;

        this.$nextTick(() => {
          this.$refs.commentInput.focus();
          this.$refs.commentInput.setSelectionRange(
            mentionStart + user.name.length + 2,
            mentionStart + user.name.length + 2
          );
        });
      }

      // Réinitialiser l'état des suggestions
      this.showSuggestions = false;
      this.filteredUsers = [];
      this.activeSuggestionIndex = 0;
    },

    dataURLtoBlob(dataURL) {
      const arr = dataURL.split(",");
      const mime = arr[0].match(/:(.*?);/)[1];
      const bstr = atob(arr[1]);
      let n = bstr.length;
      const u8arr = new Uint8Array(n);
      while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
      }
      return new Blob([u8arr], { type: mime });
    },

    closeModal() {
      this.showModal = false;
      if (this.stage) {
        this.stage.destroy();
      }
    },
    showTicketForm() {
      this.showTicketModal = true;
    },
    closeTicketsModal() {
      this.showTicketsModal = false;
    },

    closeTicketModal() {
      this.showTicketModal = false;
    },
    setActiveTicket(index) {
      this.activeTicketIndex = index;
      this.activeTicket = this.userTickets[index];

      if (this.activeTicket && this.activeTicket.id) {
        this.fetchComments(this.activeTicket.id);
      }
    },
    getStatusClass(status) {
      const statusOption = this.statusOptions.find(
        (option) => option.value === status
      );
      return statusOption ? statusOption.colorClass : "bg-gray-300 text-black"; // Classe par défaut si non trouvée
    },
    getStatusLabel(status) {
      const statusOption = this.statusOptions.find(
        (option) => option.value === status
      );
      return statusOption ? statusOption.label : status; // Retourne le label ou le statut brut
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
            "X-WP-Nonce": WPIT_Front.nonce,
          },
        }
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.notyf.success(this.translations.ticket_deleted);
            this.userTickets = this.userTickets.filter(
              (ticket) => ticket.id !== this.activeTicket.id
            );
            this.activeTicket = "";
            this.activeTicketIndex = null;
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
  