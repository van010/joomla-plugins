<template>
	<div v-if="showRes" :style="responseStyle"
		 class="ai-response">
		<p :style="waitingStyle" id="waiting-text">AI is writing<span class="dots">...</span></p>
		<div v-html="resMessage"></div>
		<p class="words-counting"> {{ utils.countWords(resMessage) }}</p>
	</div>
</template>

<script setup>
import {resMessage, responseStyle, showRes, waitingStyle} from "@/Helper/Store";
import * as utils from "@/Helper/Utilities";
</script>