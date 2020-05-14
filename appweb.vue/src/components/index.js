import Vue from 'vue'

import ListLoader from './widgets/ListLoader'
import ColorIcon from './widgets/ColorIcon'
import StateNumber from './widgets/StateNumber'
import NiftyPopup from './dialogs/NiftyPopup'
import ModalPopup from './dialogs/ModalPopup'
import Toast from './dialogs/Toast'

Vue.component(ListLoader.name, ListLoader)
Vue.component(ColorIcon.name, ColorIcon)
Vue.component(StateNumber.name, StateNumber)
Vue.component(NiftyPopup.name, NiftyPopup)
Vue.component(ModalPopup.name, ModalPopup)
Vue.use(Toast)
