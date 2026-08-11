<template>
  <VDialog
    v-model="dialog"
    max-width="600px"
  >
    <VCard>
      <VCardTitle class="headline">
        Reportar Otros Pagos
      </VCardTitle>
      <VCardText>
        <VForm
          ref="form"
          v-model="valid"
        >
          <VTextField
            v-model="paymentDescription"
            label="Descripción del Pago"
            :rules="[rules.required]"
            required
          />
          <VTextField
            v-model="paymentAmount"
            label="Monto del Pago"
            :rules="[rules.required, rules.numeric]"
            required
          />
        </VForm>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          color="blue darken-1"
          text
          @click="closeDialog"
        >
          Cancelar
        </VBtn>
        <VBtn
          color="blue darken-1"
          text
          @click="submitForm"
        >
          Reportar
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
