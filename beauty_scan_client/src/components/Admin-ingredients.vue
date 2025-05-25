<template>
  <div class="admin-ingredients">
    <h1>Управление ингредиентами</h1>

    <div class="actions">
      <button class="add-btn" @click="showCreateForm = true">
        Добавить ингредиент
      </button>
    </div>

    <div class="filters">
      <div class="filter">
        <label for="safety-filter">Фильтр по безопасности:</label>
        <select id="safety-filter" v-model="safetyFilter" @change="loadIngredients">
          <option value="">Все уровни</option>
          <option value="Низкий">Безопасные</option>
          <option value="Средний">С осторожностью</option>
          <option value="Высокий">Опасные</option>
        </select>
      </div>

      <div class="filter">
        <label for="naturalness-filter">Фильтр по натуральности:</label>
        <select id="naturalness-filter" v-model="naturalnessFilter" @change="loadIngredients">
          <option value="">Все типы</option>
          <option value="Натуральный">Натуральные</option>
          <option value="Синтетический">Синтетические</option>
        </select>
      </div>

      <div class="filter">
        <label for="search">Поиск по названию:</label>
        <input id="search" type="text" v-model="searchQuery" placeholder="Введите название..." @input="filterIngredients">
      </div>
    </div>

    <!-- Прелоадер -->
    <div class="loader-container" v-if="loading">
      <div class="loader"></div>
      <p>Загрузка ингредиентов...</p>
    </div>

    <!-- Список ингредиентов -->
    <div v-else-if="filteredIngredients.length > 0" class="ingredients-list">
      <div
          v-for="ingredient in filteredIngredients"
          :key="ingredient.id"
          class="ingredient-item"
          :class="{'Низкий': ingredient.danger_factor === 'Низкий',
               'Средний': ingredient.danger_factor === 'Средний',
               'Высокий': ingredient.danger_factor === 'Высокий'}"
      >
        <div class="ingredient-header">
          <h3 class="ingredient-title">{{ ingredient.traditional_name }}</h3>
          <div class="actions">
            <button class="edit-btn" @click="editIngredient(ingredient)">
              Ред.
            </button>
            <button class="delete-btn" @click="confirmDelete(ingredient.id)">
              Удл.
            </button>
          </div>
        </div>

        <div class="ingredient-content">
          <div class="property">
            <strong>Латинское:</strong> <span class="value">{{ ingredient.latin_name || '—' }}</span>
          </div>

          <div class="property">
            <strong>INCI:</strong> <span class="value">{{ ingredient.inci_name || '—' }}</span>
          </div>

          <div class="property">
            <strong>Безопасность:</strong> <span class="value" :class="ingredient.danger_factor">
        {{ getSafetyLevelText(ingredient.danger_factor) }}
      </span>
          </div>

          <div class="property">
            <strong>Тип:</strong> <span class="value">{{ getNaturalnessText(ingredient.naturalness) }}</span>
          </div>

          <div class="property">
            <strong>Описание:</strong>
            <div class="value compact-text">{{ ingredient.usages || '—' }}</div>
          </div>

          <div class="property" v-if="ingredient.safety">
            <strong>Острожности при применении:</strong>
            <div class="value compact-text">{{ ingredient.safety }}</div>
          </div>

          <div class="property" v-if="ingredient.safety">
            <strong>Категория:</strong>
            <div class="value compact-text">{{ ingredient.category }}</div>
          </div>

          <div class="property" v-if="ingredient.safety">
            <strong>Зона эффективности:</strong>
            <div class="value compact-text">{{ ingredient.effectiveness }}</div>
          </div>

          <div class="property" v-if="ingredient.synonyms?.length">
            <strong>Синонимы:</strong>
            <div class="value compact-text">{{ ingredient.synonyms.map(s => s.name).join(', ') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Пустой список -->
    <div v-else class="empty-list">
      <p>Ингредиенты не найдены.</p>
    </div>

    <!-- Модальное окно создания ингредиента -->
    <div class="modal" v-if="showCreateForm">
      <div class="modal-content">
        <h2>Добавление ингредиента</h2>
        <form @submit.prevent="createIngredient">
          <div class="form-row">
            <div class="form-group">
              <label for="traditional_name">Традиционное название:</label>
              <input id="traditional_name" type="text" v-model="ingredientForm.traditional_name" required>
            </div>

            <div class="form-group">
              <label for="latin_name">Латинское название:</label>
              <input id="latin_name" type="text" v-model="ingredientForm.latin_name">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="inci_name">INCI название:</label>
              <input id="inci_name" type="text" v-model="ingredientForm.inci_name">
            </div>

            <div class="form-group">
              <label for="danger_factor">Уровень безопасности:</label>
              <select id="danger_factor" v-model="ingredientForm.danger_factor" required>
                <option value="Низкий">Безопасный</option>
                <option value="Средний">С осторожностью</option>
                <option value="Высокий">Опасный</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="naturalness">Натуральность:</label>
              <select id="naturalness" v-model="ingredientForm.naturalness" required>
                <option value="Натуральный">Натуральный</option>
                <option value="Синтетический">Синтетический</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="usages">Описание и применение:</label>
            <textarea id="usages" v-model="ingredientForm.usages" rows="4" required></textarea>
          </div>

          <div class="form-group">
            <label for="safety">Информация о безопасности:</label>
            <textarea id="safety" v-model="ingredientForm.safety" rows="4"></textarea>
          </div>

          <div class="form-group">
            <label for="usages">Категория:</label>
            <textarea id="usages" v-model="ingredientForm.category" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="usages">Зона эффективности:</label>
            <textarea id="usages" v-model="ingredientForm.effectiveness" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label>Синонимы:</label>
            <div class="synonyms-container">
              <div v-for="(synonym, index) in ingredientForm.synonyms" :key="index" class="synonym-item">
                <input type="text" v-model="ingredientForm.synonyms[index]">
                <button type="button" class="remove-btn" @click="removeSynonym(index)">×</button>
              </div>
              <button type="button" class="add-synonym-btn" @click="addSynonym">+ Добавить синоним</button>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="closeForm">Отмена</button>
            <button type="submit" class="save-btn">Сохранить</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно редактирования ингредиента -->
    <div class="modal" v-if="showEditForm">
      <div class="modal-content">
        <h2>Редактирование ингредиента</h2>
        <form @submit.prevent="updateIngredient">
          <div class="form-row">
            <div class="form-group">
              <label for="edit-traditional_name">Традиционное название:</label>
              <input id="edit-traditional_name" type="text" v-model="ingredientForm.traditional_name" required>
            </div>

            <div class="form-group">
              <label for="edit-latin_name">Латинское название:</label>
              <input id="edit-latin_name" type="text" v-model="ingredientForm.latin_name">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="edit-inci_name">INCI название:</label>
              <input id="edit-inci_name" type="text" v-model="ingredientForm.inci_name">
            </div>

            <div class="form-group">
              <label for="edit-danger_factor">Уровень безопасности:</label>
              <select id="edit-danger_factor" v-model="ingredientForm.danger_factor" required>
                <option value="Низкий">Безопасный</option>
                <option value="Средний">С осторожностью</option>
                <option value="Высокий">Опасный</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="edit-naturalness">Натуральность:</label>
              <select id="edit-naturalness" v-model="ingredientForm.naturalness" required>
                <option value="Натуральный">Натуральный</option>
                <option value="Синтетический">Синтетический</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="edit-usages">Описание и применение:</label>
            <textarea id="edit-usages" v-model="ingredientForm.usages" rows="4" required></textarea>
          </div>

          <div class="form-group">
            <label for="edit-safety">Информация о безопасности:</label>
            <textarea id="edit-safety" v-model="ingredientForm.safety" rows="4"></textarea>
          </div>

          <div class="form-group">
            <label for="edit-usages">Категория:</label>
            <textarea id="edit-usages" v-model="ingredientForm.category" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="edit-usages">Зона эффективности:</label>
            <textarea id="edit-usages" v-model="ingredientForm.effectiveness" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label>Синонимы:</label>
            <div class="synonyms-container">
              <div v-for="(synonym, index) in ingredientForm.synonyms" :key="index" class="synonym-item">
                <input type="text" v-model="ingredientForm.synonyms[index]">
                <button type="button" class="remove-btn" @click="removeSynonym(index)">×</button>
              </div>
              <button type="button" class="add-synonym-btn" @click="addSynonym">+ Добавить синоним</button>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="closeForm">Отмена</button>
            <button type="submit" class="save-btn">Сохранить</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal" v-if="showDeleteConfirm">
      <div class="modal-content">
        <h3>Подтверждение удаления</h3>
        <p>Вы уверены, что хотите удалить этот ингредиент? Это действие нельзя отменить.</p>
        <div class="confirm-actions">
          <button class="cancel-btn" @click="showDeleteConfirm = false">Отмена</button>
          <button class="delete-btn" @click="deleteIngredient">Удалить</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminIngredients',
  data() {
    return {
      ingredients: [],
      filteredIngredients: [],
      loading: true,
      safetyFilter: '',
      naturalnessFilter: '',
      searchQuery: '',
      showCreateForm: false,
      showEditForm: false,
      showDeleteConfirm: false,
      ingredientToDelete: null,
      ingredientForm: {
        id: null,
        traditional_name: '',
        latin_name: '',
        inci_name: '',
        danger_factor: 'Низкий',
        naturalness: 'Натуральный',
        usages: '',
        safety: '',
        synonyms: [],
        category: '',
        effectiveness: ''
      }
    };
  },
  created() {
    this.loadIngredients();
  },
  methods: {
    async loadIngredients() {
      this.loading = true;
      try {
        const params = {};
        if (this.safetyFilter) {
          params.danger_factor = this.safetyFilter;
        }
        if (this.naturalnessFilter) {
          params.naturalness = this.naturalnessFilter;
        }

        const response = await this.$http.get('/ingredients', { params });
        this.ingredients = response.data.map(ingredient => ({
          ...ingredient,
          latin_name: ingredient.latin_name || '',
          inci_name: ingredient.inci_name || '',
          safety: ingredient.safety || '',
          traditional_name: ingredient.traditional_name || '',
          danger_factor: ingredient.danger_factor || '',
          naturalness: ingredient.naturalness || '',
          usages: ingredient.usages || '',
          synonyms: ingredient.synonyms || [],
          category: ingredient.category || '',
          effectiveness: ingredient.effectiveness || ''
        }));
        this.filterIngredients();
      } catch (error) {
        console.error('Ошибка при загрузке ингредиентов:', error);
        alert('Ошибка при загрузке ингредиентов. Попробуйте позже.');
      } finally {
        this.loading = false;
      }
    },

    filterIngredients() {
      if (!this.searchQuery) {
        this.filteredIngredients = [...this.ingredients];
        return;
      }

      const query = this.searchQuery.toLowerCase();
      this.filteredIngredients = this.ingredients.filter(ingredient => {
        return (ingredient.traditional_name && ingredient.traditional_name.toLowerCase().includes(query)) ||
            (ingredient.latin_name && ingredient.latin_name.toLowerCase().includes(query)) ||
            (ingredient.inci_name && ingredient.inci_name.toLowerCase().includes(query)) ||
            (ingredient.usages && ingredient.usages.toLowerCase().includes(query));
      });
    },

    getSafetyLevelText(level) {
      switch (level) {
        case 'Низкий': return 'Безопасный';
        case 'Средний': return 'С осторожностью';
        case 'Высокий': return 'Опасный';
        default: return 'Неизвестно';
      }
    },

    getNaturalnessText(type) {
      switch (type) {
        case 'Натуральный': return 'Натуральный';
        case 'Синтетический': return 'Синтетический';
        default: return 'Неизвестно';
      }
    },

    editIngredient(ingredient) {
      this.ingredientForm = {
        id: ingredient.id,
        traditional_name: ingredient.traditional_name,
        latin_name: ingredient.latin_name || '',
        inci_name: ingredient.inci_name || '',
        danger_factor: ingredient.danger_factor,
        naturalness: ingredient.naturalness || 'Натуральный',
        usages: ingredient.usages,
        safety: ingredient.safety || '',
        synonyms: ingredient.synonyms ? ingredient.synonyms.map(s => s.name) : [],
        category: ingredient.category || '',
        effectiveness: ingredient.effectiveness || ''
      };
      this.showEditForm = true;
    },

    confirmDelete(id) {
      this.ingredientToDelete = id;
      this.showDeleteConfirm = true;
    },

    closeForm() {
      this.showCreateForm = false;
      this.showEditForm = false;
      this.resetForm();
    },

    resetForm() {
      this.ingredientForm = {
        id: null,
        traditional_name: '',
        latin_name: '',
        inci_name: '',
        danger_factor: 'Низкий',
        naturalness: 'Натуральный',
        usages: '',
        safety: '',
        synonyms: [],
        category: '',
        effectiveeness : ''  ,
      };
    },

    addSynonym() {
      this.ingredientForm.synonyms.push('');
    },

    removeSynonym(index) {
      this.ingredientForm.synonyms.splice(index, 1);
    },

    async createIngredient() {
      try {
        await this.$http.post('/ingredients', {
          traditional_name: this.ingredientForm.traditional_name,
          latin_name: this.ingredientForm.latin_name,
          inci_name: this.ingredientForm.inci_name,
          danger_factor: this.ingredientForm.danger_factor,
          naturalness: this.ingredientForm.naturalness,
          usages: this.ingredientForm.usages,
          safety: this.ingredientForm.safety,
          synonyms: this.ingredientForm.synonyms.filter(s => s.trim() !== ''),
          category: this.ingredientForm.category,
          effectiveness: this.ingredientForm.effectiveness
        });

        await this.loadIngredients();
        this.showCreateForm = false;
        this.resetForm();
      } catch (error) {
        console.error('Ошибка при создании ингредиента:', error);
        alert('Ошибка при создании ингредиента. Попробуйте позже.');
      }
    },

    async updateIngredient() {
      try {
        await this.$http.put(`/ingredients/${this.ingredientForm.id}`, {
          traditional_name: this.ingredientForm.traditional_name,
          latin_name: this.ingredientForm.latin_name,
          inci_name: this.ingredientForm.inci_name,
          danger_factor: this.ingredientForm.danger_factor,
          naturalness: this.ingredientForm.naturalness,
          usages: this.ingredientForm.usages,
          safety: this.ingredientForm.safety,
          synonyms: this.ingredientForm.synonyms.filter(s => s.trim() !== ''),
          category: this.ingredientForm.category,
          effectiveness: this.ingredientForm.effectiveness
        });

        await this.loadIngredients();
        this.showEditForm = false;
        this.resetForm();
      } catch (error) {
        console.error('Ошибка при обновлении ингредиента:', error);
        alert('Ошибка при обновлении ингредиента. Попробуйте позже.');
      }
    },

    async deleteIngredient() {
      try {
        await this.$http.delete(`/ingredients/${this.ingredientToDelete}`);

        await this.loadIngredients();
        this.showDeleteConfirm = false;
        this.ingredientToDelete = null;
      } catch (error) {
        console.error('Ошибка при удалении ингредиента:', error);
        alert('Ошибка при удалении ингредиента. Попробуйте позже.');
      }
    }
  }
};
</script>

<style scoped>
.admin-ingredients {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
}

.actions {
  margin-bottom: 20px;
  text-align: right;
}

.add-btn {
  padding: 10px 20px;
  background-color: #2ecc71;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.filters {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.filter {
  flex: 1;
}

.filter label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.filter select, .filter input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.loader-container {
  text-align: center;
  padding: 50px 0;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.ingredients-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.ingredient-item {
  background-color: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  border-left: 5px solid #ccc;
  width: 370px;
}

.ingredient-item.Низкий {
  border-left-color: #2ecc71;
}

.ingredient-item.Средний {
  border-left-color: #f39c12;
}

.ingredient-item.Высокий {
  border-left-color: #e74c3c;
}

.ingredient-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}

.ingredient-header h3 {
  margin: 0;
}

.actions {
  display: flex;
  gap: 10px;
}

.edit-btn, .delete-btn {
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
}

.edit-btn {
  background-color: #3498db;
  color: white;
  border: none;
}

.delete-btn {
  background-color: #e74c3c;
  color: white;
  border: none;
}

.ingredient-content > div {
  margin-bottom: 10px;
}

.name-variants div {
  margin-bottom: 5px;
}

.characteristics {
  display: flex;
  gap: 20px;
  margin: 10px 0;
}

.safety .Низкий {
  color: #2ecc71;
  font-weight: bold;
}

.safety .Средний {
  color: #f39c12;
  font-weight: bold;
}

.safety .Высокий {
  color: #e74c3c;
  font-weight: bold;
}

.empty-list {
  text-align: center;
  padding: 50px 0;
  color: #666;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h2, .modal-content h3 {
  margin-top: 0;
  margin-bottom: 20px;
}

.form-row {
  display: flex;
  gap: 15px;
}

.form-row .form-group {
  flex: 1;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.synonyms-container {
  margin-top: 10px;
}

.synonym-item {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.remove-btn {
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  width: 30px;
  font-size: 1.2rem;
}

.add-synonym-btn {
  background-color: transparent;
  color: #3498db;
  border: 1px dashed #3498db;
  border-radius: 4px;
  padding: 8px;
  cursor: pointer;
  width: 100%;
}

.form-actions, .confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.cancel-btn {
  padding: 8px 15px;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.save-btn {
  padding: 8px 15px;
  background-color: #2ecc71;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.ingredient-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.9em;
}

.property {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 4px;
  line-height: 1.3;
}

.property > strong {
  flex-shrink: 0;
}

.value {
  word-break: break-word;
  overflow-wrap: break-word;
  hyphens: auto;
}
.compact-text {
  max-height: 80px;
  overflow-y: auto;
  padding: 2px 4px;
  background: #f8f8f8;
  border-radius: 2px;
  font-size: 0.9em;
  width: 100%;
}

/* Уменьшаем отступы в заголовке */
.ingredient-header {
  padding-bottom: 6px;
  margin-bottom: 8px;
}

.edit-btn, .delete-btn {
  padding: 3px 8px;
  min-width: 40px; /* Фиксированная ширина кнопок */
  font-size: 0.8em;
  height: 24px; /* Фиксированная высота кнопок */
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Уменьшаем общие отступы */
.ingredient-item {
  padding: 10px;
}

/* Уменьшаем отступы между карточками */
.ingredients-list {
  gap: 12px;
}

.ingredient-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
  padding-bottom: 6px;
  border-bottom: 1px solid #eee;
  min-height: 32px; /* Фиксированная высота */
}

.ingredient-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex-grow: 1;
  margin: 0;
  padding-right: 10px;
  font-size: 1em;
  line-height: 1.2;
}

.header-actions {
  display: flex;
  flex-shrink: 0;
  gap: 6px;
}
</style>