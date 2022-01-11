<template>
  <div class="forget-password">
    <div class="forget-password-form">
      <form action="">
        <div class="text-center mb-3">
          <h3 style="font-weight: bold">WELCOME</h3>
          <p>Vui lòng nhập thông tin bên dưới</p>
        </div>
        
        <div class="mb-3">
          <label for="">Email <span class="text-danger font-weight-bold">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            </div>
            <input type="email" class="form-control" v-model="email" />
          </div>
          <div class="text-danger">{{ email_error }}</div>
        </div>

        <div>
          <button 
            type="button"
            class="btn btn-primary"
            style="width: 100%"
            @click="submitForm"
          >
            Gửi đến email
          </button>
          <div style="border-top: 1px solid #ddd;" class="mt-4 ml-5 mr-5 mb-1"></div>
          <div class="text-center">
            <router-link :to="{ name: 'login' }">Đăng nhập</router-link>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>