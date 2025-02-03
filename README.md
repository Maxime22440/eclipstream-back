pour ma page de streaming que dois je changer pour le fonctionnement sans cookie ?
car actuellement j'utilise bearer mais ça ne marche pas


<template>
  <div class="video-page">
    <div v-if="error"
         class="error">
      <p>{{ error }}</p>
    </div>

    <div v-else>
      <!-- Section poster avec le bouton play -->
      <div class="video-poster">
        <div v-if="!isPlaying">
          <img v-if="getImageComponentOrPath(poster)"
               :src="getImageComponentOrPath(poster)"
               alt="Poster du contenu"
               @load="imageLoaded = true"
               class="poster-image"
               :class="{ 'loaded': imageLoaded }"/>

          <!-- Si l'image n'est pas disponible -->
          <div v-else
               class="poster-placeholder"
               :class="{ 'hidden': imageLoaded }">
            <MovieIcon class="poster-icon"/>
          </div>
          <template v-if="imageLoaded">
            <button v-if="isAuthenticated"
                    class="play-button"
                    @click="playVideo">
              <i class="fas fa-play"></i>
            </button>
            <button v-else
                    class="lock-button"
                    @click="showLoginNotification">
              <i class="fas fa-lock"></i>
            </button>
          </template>
        </div>

        <!-- Affiche le lecteur vidéo si la vidéo est en lecture -->
        <div v-else>
          <!-- Affiche une erreur si elle existe -->
          <div v-if="error"
               class="video-error">
            {{ error }}
          </div>

          <!-- Lecteur vidéo -->
          <video
            ref="videoPlayer"
            class="video-js"
            controls
            autoplay
            preload="auto"
            controlsList="nodownload"
            oncontextmenu="return false;"
          >
            <source :src="fullVideoLink"
                    type="video/mp4"/>
          </video>
        </div>
      </div>
        <EpisodeNavigation
          v-if="isEpisodeNavigationVisible"
          :isFirstEpisode="isFirstEpisode"
          :isLastEpisode="isLastEpisode"
          :currentEpisodeNumber="currentEpisode?.episodeNumber || 1"
          :currentEpisodeUuid="route.params.episodeUuid || ''"
          :currentSeasonNumber="currentSeason?.seasonNumber || 1"
          :seasons="content.seasons"
          @previous="navigateToPrevious"
          @next="navigateToNext"
          @seasonChange="onSeasonChange"
          @episodeChange="onEpisodeChange"
        />

        <!-- Contenu sous le lecteur -->
        <div class="video-content">
          <!-- Partie gauche (Thumbnail) -->
          <div v-if="!isSmallScreen"
               class="thumbnail">
            <img v-if="getImageComponentOrPath(thumbnail)"
                 :src="getImageComponentOrPath(thumbnail)"
                 alt="Thumbnail du contenu"
                 class="thumbnail-image"
                 :class="{ 'loaded': imageLoaded }"/>

            <!-- Si l'image n'est pas disponible -->
            <div v-else
                 class="thumbnail-placeholder"
                 :class="{ 'hidden': imageLoaded }">
            </div>
          </div>

          <!-- Partie droite (Détails du film) -->
          <div class="details">
            <!-- Titre et notation -->
            <div class="title-rating">
              <!-- Titre de la série -->
              <span class="series-title">{{ content.title }}</span>

              <!-- Titre de l'épisode et rating (uniquement si épisode présent) -->
              <template v-if="episode">
            <span class="episode-rating">
              <span class="episode-title">- {{ episode.title }}</span>
              <span class="rating">
                <i class="fas fa-star"></i>
                {{ episode.rating }}
              </span>
            </span>
              </template>

              <!-- Rating uniquement pour un contenu sans épisode -->
              <template v-else>
            <span class="rating">
              <i class="fas fa-star"></i>
              {{ content.rating }}
            </span>
              </template>
            </div>

            <!-- Saison et épisode (affichés uniquement pour les séries) -->
            <div v-if="episode"
                 class="episode-metadata">
              <p>Saison {{ currentSeason?.seasonNumber }} - Épisode {{ episode.episodeNumber }}</p>
            </div>

            <!-- Genres, pays et durée -->
            <div class="metadata">
            <span class="genre"
                  v-for="genre in content.genres"
                  :key="genre">{{ genre }}</span>
              <span class="country">{{ content.country }}</span>
              <span class="duration">{{
                  episode ? formatDuration(episode.duration) : formatDuration(content.duration)
                }}</span>
            </div>

            <!-- Description -->
            <p class="description">{{ episode ? episode.description : content.description }}</p>

            <!-- Casting -->
            <div class="casting">
              <h2>Acteurs principaux</h2>
              <div class="cast-list">
              <span v-for="(actor, index) in content.cast"
                    :key="actor">
                {{ actor }}
                <span v-if="index !== content.cast.length - 1">, </span>
              </span>
              </div>
            </div>
          </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {ref, onMounted, computed, watch} from 'vue';
import {useRoute} from 'vue-router';
import {useRouter} from 'vue-router';
import { useUserStore } from '@/stores/useUserStore';
import { useNotificationStore } from '@/stores/useNotificationStore';
import {fetchContent} from '@/api/content'; // Utilisation de ta méthode API existante
import EpisodeNavigation from "@/components/Content/EpisodeNavigation.vue";
import MovieIcon from '@/components/icons/MovieIcon.vue';

const userStore = useUserStore();
const notificationStore = useNotificationStore();
const isAuthenticated = computed(() => userStore.isAuthenticated);
const backendUrl = import.meta.env.VITE_BACKEND_URL;

// Récupération de l'UUID depuis les paramètres de l'URL
const route = useRoute();
const router = useRouter();
const contentUuid = (route.params.contentUuid || route.params.uuid) as string;
const episodeUuid = route.params.episodeUuid as string | null;

// Variables réactives pour les données du contenu
const content = ref({
  title: '',
  type: '', // `movie`, `series`, etc.
  rating: 0,
  genres: [] as string[], // Liste des genres (noms uniquement)
  country: '',
  duration: '', // Durée totale (en minutes ou formatée)
  description: '',
  cast: [] as string[], // Liste des acteurs (noms uniquement)
  stream_link: '',
  seasons: [] as Array<{
    seasonNumber: number; // Numéro de la saison
    episodes: Array<{
      uuid: string,
      episodeNumber: number; // Numéro de l'épisode
      title: string; // Titre de l'épisode
      duration: number; // Durée en minutes
      description: string; // Description de l'épisode
      stream_link?: string;
    }>;
  }>, // Liste des saisons et leurs épisodes
});

// Fonction pour déclencher une notification
const showLoginNotification = () => {
  notificationStore.addNotification('error', 'Veuillez vous connecter pour regarder ce contenu.');
};

const currentSeason = computed(() => {
  return content.value.seasons.find((season) =>
    season.episodes.some((episode) => episode.uuid === episodeUuid)
  );
});

const episode = computed(() => {
  if (!currentSeason.value) return null;
  return currentSeason.value.episodes.find((ep) => ep.uuid === episodeUuid);
});

// Calcul pour déterminer si la navigation des épisodes doit être affichée
const isEpisodeNavigationVisible = computed(() => {
  return content.value.type === 'series' || content.value.type === 'anime-series';
});

const poster = ref('');
const thumbnail = ref('');
const isSmallScreen = ref(window.innerWidth < 768);
const error = ref<string | null>(null);
const imageLoaded = ref(false);
const videoLink = ref('');
const isPlaying = ref(false); // Variable pour afficher ou non le lecteur vidéo

// Fonction pour récupérer le contenu depuis l'API
const loadContent = async () => {
  try {
    const data = await fetchContent(contentUuid); // Appel de ta méthode API `fetchContent`
    console.log('Données reçues depuis l’API:', data); // Vérifie les acteurs ici

    // Mise à jour des données du contenu
    content.value = {
      title: data.title,
      type: data.type, // Utilisé pour afficher des champs conditionnels
      rating: data.imdb_rating,
      genres: data.genres.map((genre: any) => genre.name),
      country: data.country,
      duration: data.duration, // Durée en minutes
      description: data.description,
      cast: data.actors.map((actor: any) => actor.name),
      stream_link: data.stream_link,
      seasons: (data.seasons || []).map((season: any) => ({
        seasonNumber: season.season_number,
        episodes: (season.episodes || []).map((episode: any) => ({
          uuid: episode.uuid, // UUID de l'épisode
          episodeNumber: episode.episode_number,
          title: episode.title,
          rating: episode.imdb_rating,
          duration: episode.duration,
          description: episode.description,
          stream_link: episode.stream_link,
        })),
      })) // Inclure les saisons et les épisodes
    };
    console.log('Données des saisons reçues :', content.value.seasons);
    console.log('UUID d’épisode actif :', episodeUuid);


    poster.value = data.poster_path;
    thumbnail.value = data.thumbnail_path;

    // Détermination du stream_link en fonction du type de contenu
    if (episodeUuid) {
      const currentEp = episode.value;
      if (currentEp && currentEp.stream_link) {
        videoLink.value = currentEp.stream_link;
      } else {
        console.warn('Stream link pour l’épisode non trouvé.');
        videoLink.value = ''; // Ou une valeur par défaut
      }
    } else {
      videoLink.value = data.stream_link;
    }

    console.log('Lien relatif vidéo (API):', videoLink.value);
    console.log('Lien complet vidéo:', fullVideoLink.value);
  } catch (err) {
    error.value = 'Erreur lors de la récupération des données.';
    console.error(err);
  }
};

const playVideo = () => {
  isPlaying.value = true;
  error.value = null; // Réinitialise les erreurs
  console.log('Lecture vidéo via :', fullVideoLink.value);
};

// Fonction pour formater la durée en minutes vers hh:mm
const formatDuration = (minutes: string | number | null) => {
  if (!minutes || isNaN(Number(minutes))) return 'Durée inconnue';

  const totalMinutes = Number(minutes);
  const hours = Math.floor(totalMinutes / 60);
  const remainingMinutes = totalMinutes % 60;

  // Retourne uniquement les minutes si moins de 1 heure
  if (hours < 1) {
    return `${remainingMinutes}m`;
  }

  // Retourne le format "XhYm" si plus d'une heure
  return `${hours}h${remainingMinutes}m`;
};

const getImageComponentOrPath = (path: string) => {
  const baseUrl = backendUrl.replace('/api', ''); // Retire `/api` si présent
  return path
    ? `${baseUrl}/storage/${path}` // Chemin de l'image si disponible
    : null; // Retourne null si aucune image
};

const fullVideoLink = computed(() => {
  if (!videoLink.value) {
    console.log('videoLink.value est vide ou non défini');
    return '';
  }
  const result = `${backendUrl}${videoLink.value}`;
  console.log('fullVideoLink calculé :', result);
  return result;
});

// Écouter le redimensionnement de l'écran
window.addEventListener('resize', () => {
  isSmallScreen.value = window.innerWidth < 768;
});

onMounted(() => {
  if (!contentUuid) {
    error.value = 'Aucun UUID de contenu fourni dans l’URL.';
    return;
  }

  loadContent();
});

const onSeasonChange = (newSeasonNumber: number) => {
  console.log("Changement de saison détecté :", newSeasonNumber);

  // Trouver la nouvelle saison sélectionnée
  const selectedSeason = content.value.seasons.find(
    (season) => season.seasonNumber === newSeasonNumber
  );
  console.log("Saison sélectionnée :", selectedSeason);

  // Si la saison ou les épisodes ne sont pas valides, on arrête là
  if (!selectedSeason || selectedSeason.episodes.length === 0) {
    console.warn("Aucune saison valide trouvée ou pas d'épisodes disponibles.");
    return;
  }

  // Récupérer le premier épisode de la saison sélectionnée
  const firstEpisode = selectedSeason.episodes[0];
  console.log("Premier épisode de la saison :", firstEpisode);

  // Met à jour l'URL pour refléter le changement
  router.push(`/content-player/${contentUuid}/episode/${firstEpisode.uuid}`);
  console.log("Navigation vers :", `/content-player/${contentUuid}/episode/${firstEpisode.uuid}`);
};

const onEpisodeChange = (episodeUuid: string) => {
  router.push(`/content-player/${contentUuid}/episode/${episodeUuid}`);
};

const currentEpisode = computed(() => {
  if (!currentSeason.value) return null;
  return currentSeason.value.episodes.find((ep) => ep.uuid === episodeUuid);
});

const previousEpisode = computed(() => {
  if (!currentSeason.value) return null;

  // Trouver l'index de l'épisode actuel dans la saison courante
  const currentIndex = currentSeason.value.episodes.findIndex((ep) => ep.uuid === episodeUuid);

  // Si un épisode précédent existe dans la même saison
  if (currentIndex > 0) {
    return currentSeason.value.episodes[currentIndex - 1];
  }

  // Sinon, chercher la saison précédente
  const previousSeason = content.value.seasons.find(
    (season) => season.seasonNumber === currentSeason.value.seasonNumber - 1
  );

  // Retourner le dernier épisode de la saison précédente si elle existe
  if (previousSeason && previousSeason.episodes.length > 0) {
    return previousSeason.episodes.at(-1); // Dernier épisode de la saison précédente
  }

  // Aucun épisode précédent disponible
  return null;
});

const nextEpisode = computed(() => {
  if (!currentSeason.value) return null;

  // Trouver l'index de l'épisode actuel dans la saison courante
  const currentIndex = currentSeason.value.episodes.findIndex((ep) => ep.uuid === episodeUuid);

  // Si un épisode suivant existe dans la même saison
  if (currentIndex < currentSeason.value.episodes.length - 1) {
    return currentSeason.value.episodes[currentIndex + 1];
  }

  // Sinon, chercher la saison suivante
  const nextSeason = content.value.seasons.find(
    (season) => season.seasonNumber === currentSeason.value.seasonNumber + 1
  );

  // Retourner le premier épisode de la saison suivante si elle existe
  if (nextSeason && nextSeason.episodes.length > 0) {
    return nextSeason.episodes[0];
  }

  // Aucun épisode suivant disponible
  return null;
});

const isFirstEpisode = computed(() => {
  console.log("=== Calcul de isFirstEpisode ===");
  console.log("currentSeason:", currentSeason.value);
  console.log("episodeUuid:", episodeUuid);

  if (!currentSeason.value || !currentSeason.value.episodes.length) {
    console.log("Retourne false car currentSeason est invalide ou sans épisodes.");
    return false;
  }

  const isFirst =
    currentSeason.value.seasonNumber === 1 &&
    currentSeason.value.episodes[0].uuid === episodeUuid;

  console.log("isFirstEpisode:", isFirst);
  return isFirst;
});

const isLastEpisode = computed(() => {
  console.log("=== Calcul de isLastEpisode ===");
  const lastSeason = content.value.seasons.at(-1);
  console.log("currentSeason:", currentSeason.value);
  console.log("lastSeason:", lastSeason);
  console.log("episodeUuid:", episodeUuid);

  if (!lastSeason || !lastSeason.episodes.length || !currentSeason.value) {
    console.log("Retourne false car lastSeason ou currentSeason est invalide.");
    return false;
  }

  const isLast =
    currentSeason.value.seasonNumber === lastSeason.seasonNumber &&
    currentSeason.value.episodes.at(-1).uuid === episodeUuid;

  console.log("isLastEpisode:", isLast);
  return isLast;
});

// Gestion de la navigation "Précédent"
const navigateToPrevious = () => {
  if (previousEpisode.value) {
    router.push(`/content-player/${contentUuid}/episode/${previousEpisode.value.uuid}`);
  }
};

// Gestion de la navigation "Suivant"
const navigateToNext = () => {
  if (nextEpisode.value) {
    router.push(`/content-player/${contentUuid}/episode/${nextEpisode.value.uuid}`);
  }
};

watch(
  () => [route.params.contentUuid, route.params.episodeUuid],
  ([newContentUuid, newEpisodeUuid], [oldContentUuid, oldEpisodeUuid]) => {
    if (newContentUuid !== oldContentUuid || newEpisodeUuid !== oldEpisodeUuid) {
      console.log('Changement détecté : recharge des données');
      loadContent();
    }
  }
);

watch(() => isFirstEpisode.value, (newVal) => {
  console.log('isFirstEpisode:', newVal);
});

watch(() => isLastEpisode.value, (newVal) => {
  console.log('isLastEpisode:', newVal);
});

watch(fullVideoLink, (newVal) => {
  console.log('Lien vidéo mis à jour :', newVal);
});
</script>
