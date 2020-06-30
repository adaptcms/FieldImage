<template>
  <div>
    <template v-if="!isMultiple">
      <a :href="value" target="_blank">
        <img :src="value" class="rounded h-16 w-16" />
      </a>
    </template>

    <template v-if="isMultiple">
      <div class="flex flex-wrap -mx-2">
        <div v-for="(image, index) in images" :key="`image-${index}`" class="w-auto px-2 mb-2">
          <a :href="image" target="_blank">
            <img :src="image" class="rounded h-16 w-16" />
          </a>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import get from 'lodash.get'
import isEmpty from 'lodash.isempty'

export default {
  props: [
    'value',
    'field',
    'model',
    'module',
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
