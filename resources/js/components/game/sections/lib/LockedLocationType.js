export default class LockedLocationType {

  static get PURGATORYSMITHSHOUSE() {
    return 0;
  }

  static getEffect(level) {

    switch (level) {
      case 1:
        return {
          'devouring_darkness_range': '0.001-0.003%',
          'stat_bonus_increase': '1-3%'
        }
      case 2:
        return {
          'devouring_darkness_range': '0.001-0.005%',
          'stat_bonus_increase': '1-5%'
        }
      case 3:
        return {
          'devouring_darkness_range': '0.001-0.008%',
          'stat_bonus_increase': '1-8%'
        }
      case 4:
        return {
          'devouring_darkness_range': '0.001-0.01%',
          'stat_bonus_increase': '1-10%'
        }
      case 5:
        return {
          'devouring_darkness_range': '0.001-0.015%',
          'stat_bonus_increase': '1-15%'
        }
      default:
        return {
          'devouring_darkness_range': 'ERROR.',
          'stat_bonus_increase': 'ERROR.'
        }
    }
  }
}
