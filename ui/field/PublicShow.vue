<template>
  <div>
    <template v-if="!isMultiple">
      <a v-if="value" :href="value" target="_blank" rel="noopener">
        <img :src="value" class="rounded h-16 w-16" />
      </a>
    </template>

    <template v-if="isMultiple">
      <div class="flex flex-wrap -mx-2">
        <div v-for="(image, index) in images" :key="`image-${index}`" class="w-auto px-2 mb-2">
          <a v-if="image" :href="image" target="_blank" rel="noopener">
            <img :src="image" class="rounded h-16 w-16" />
          </a>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { get } from 'lodash'

export default {
  props: [
    'value',
    'field',
    'model',
    'package',
    'action'
  ],

  computed: {
    isMultiple () {
      return (get(this.field, 'meta.mode', 'single') === 'multiple')
    },

    images () {
      if (this.isMultiple) {
        return JSON.parse(this.value)
      }
    }
  }
}
</script>
